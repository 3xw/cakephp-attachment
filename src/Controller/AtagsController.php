<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\UnauthorizedException;

class AtagsController extends AppController
{
  use \Crud\Controller\ControllerTrait;

  public array $paginate = [
        // CakePHP 5 defaults `maxLimit` to 100; bump both so the sidebar
        // gets the full atag list in one shot.
        'limit'    => 100000,
        'maxLimit' => 100000,
        // Respect AtagTypes.order first, fall back on alpha-sorted Atags.name
        // so the sidebar groups in the same order as the admin-defined types.
        // (AtagTypes contain is added by beforePaginate so the sort works.)
        'order' => [
            'AtagTypes.order' => 'ASC',
            'AtagTypes.name'  => 'ASC',
            'Atags.name'      => 'ASC',
        ],
    ];

  public function initialize(): void
  {
    parent::initialize();

    $this->loadComponent('Crud.Crud', [
      'actions' => [
        'index'  => ['className' => 'Crud.Index'],
        'view'   => ['className' => 'Crud.View'],
        'add'    => ['className' => 'Crud.Add'],
        'edit'   => ['className' => 'Crud.Edit'],
        'delete' => ['className' => 'Crud.Delete'],
      ],
      'listeners' => [
        'Crud.Api',
        'Crud.ApiQueryLog',
      ],
    ]);
  }

  /**
   * Return the user-id of the caller, read from the Authentication
   * middleware identity attribute. Null on stateless / unauthenticated
   * requests (which then can't see anyone's personal atags).
   */
  protected function currentUserId(): ?string
  {
    $identity = $this->getRequest()->getAttribute('identity');
    if ($identity === null) return null;
    $id = method_exists($identity, 'getIdentifier') ? $identity->getIdentifier() : null;
    if ($id === null) {
      $arr = is_array($identity) ? $identity : (method_exists($identity, 'toArray') ? $identity->toArray() : []);
      $id = $arr['id'] ?? null;
    }
    return $id ? (string)$id : null;
  }

  public function index()
  {
    $userId = $this->currentUserId();
    // uuid is the legacy session-control handle — optional in stateless API.
    $this->Crud->on('beforePaginate', function(Event $event) use ($userId)
    {
      $query = $event->getSubject()->query->contain(['AtagTypes']);

      // Personal atag visibility: each row is either public (user_id IS NULL)
      // or owned by the current caller. Owners' personal atags (used for
      // "Favoris" / per-user selections) stay private to them.
      if ($userId !== null) {
        $query->where(['OR' => [
          'Atags.user_id IS' => null,
          'Atags.user_id'     => $userId,
        ]]);
      } else {
        $query->where(['Atags.user_id IS' => null]);
      }

      // if(Configure::read('Trois/Attachment.browse.filter_tags')){
      //   $type = $this->request->getQuery('type') == '' ? 'all' : explode('/',$this->request->getQuery('type'))[0];
      //   if($type == 'all'){
      //     $query->matching('Attachments')->group(['Atags.id']);
      //   }else {
      //     $query->matching('Attachments', function ($q) {
      //       return $q->where(['Attachments.type' => explode('/', $this->request->getQuery('type'))[0]]);
      //     })->group(['Atags.id']);
      //   }
      // }
      if(Configure::read('Trois/Attachment.translate'))
      {
        $event->getSubject()->query->find('translations');
      }

      if (Configure::read('Trois/Attachment.browse.only_used_tags') && $this->request->getQuery('mode') == 'browse') {
        $selectedTags = $this->request->getQuery('selected') == '' ? [] : explode(',', $this->request->getQuery('selected'));

        $selectedTags = array_map(function ($tag) {
          return $this->Atags->find('all')->where(['name' => str_replace('+', ' ', $tag)])->first()->id;
        }, $selectedTags);

        // si user filter tags ajout des tags de l'utilisateur
        if(!empty(Configure::read('Trois/Attachment.browse.user_filter_tag_types'))){
          $usersTable = $this->fetchTable('Users');
          $id = $this->getRequest()->getSession()->read('Auth')->id;
          $user = $usersTable->get($id, ['contain' => ['Atags']]);
          $tagsIds = [];
          if(!empty($user->atags))
          {
            foreach($user->atags as $tag) $tagsIds[] = $tag['id'];
          }
          $selectedTags = array_merge($selectedTags, $tagsIds);
        }

        $connection = ConnectionManager::get('default');


        if (!isset ($selectedTags) || empty ($selectedTags)) {
          $usedTags = $connection->execute('
            SELECT atag_id
            FROM attachments_atags
            GROUP BY atag_id'
          )->fetchAll('assoc');

          $usedTags = array_map(function ($tag) {
            return $tag['atag_id'];
          }, $usedTags);

          $query->where(['Atags.id IN' => $usedTags]);
        } else {

          $usedTags = $connection->execute('
            SELECT atag_id
            FROM attachments_atags
            WHERE attachment_id IN (
              SELECT attachment_id
              FROM attachments_atags
              WHERE atag_id IN (' . implode(',', $selectedTags) . ')
              GROUP BY attachment_id
              HAVING COUNT(DISTINCT atag_id) = ' . sizeof($selectedTags) . '
            )
            GROUP BY atag_id
          ')->fetchAll('assoc');

          $usedTags = array_map(function ($tag) {
            return $tag['atag_id'];
          }, $usedTags);

          if (sizeof($usedTags) > 0) {
            $query->where(['Atags.id IN' => $usedTags]);
          } else {
            $query->where(['Atags.id' => 0]);
          }
        }
      }
    });

    return $this->Crud->execute();
  }

  /**
   * Faceted-filter helper: for each atag, return the number of attachments
   * that would match the **current** filter set (search, type, date, the
   * already-active atag selection). The frontend uses these counts to render
   * the sidebar with live numbers and to grey out (or hide) tags that are
   * unreachable from the current context.
   *
   * Querystring (all optional):
   *   q         search term (matches Search behaviour on Attachments)
   *   type      attachment type (image|video|application|…)
   *   date      "YYYY-MM-DD" or "YYYY-MM-DD,YYYY-MM-DD"
   *   atags     CSV of atag slugs already active (intersection)
   *
   * Returns: { counts: { atag_id: int } }
   *
   * Implementation: a single GROUP BY over the pivot, joined to attachments
   * and filtered by the same WHERE clauses the index uses.
   */
  public function counts()
  {
    $req = $this->getRequest();
    $q       = (string)$req->getQuery('q', '');
    $type    = (string)$req->getQuery('type', '');
    $date    = (string)$req->getQuery('date', '');
    $atagsCsv = (string)$req->getQuery('atags', '');

    $Attachments = $this->fetchTable('Trois/Attachment.Attachments');

    // Start from attachments matching the *non-tag* filters, then GROUP BY
    // tag through the pivot. This way selecting tag X gives us counts for
    // every other tag intersected with X (classic faceted intersection).
    $base = $Attachments->find();
    $base
      ->select([
        'atag_id' => 'AttachmentsAtags.atag_id',
        'count'   => $base->func()->count($base->newExpr('DISTINCT Attachments.id')),
      ])
      ->innerJoin(
        ['AttachmentsAtags' => 'attachments_atags'],
        ['AttachmentsAtags.attachment_id = Attachments.id']
      )
      ->groupBy('AttachmentsAtags.atag_id');

    // Hide other users' personal atags from the counts payload too — the
    // sidebar already filters them out client-side, this just removes them
    // from the response so payload size stays minimal.
    $userId = $this->currentUserId();
    if ($userId !== null) {
      $base->innerJoin(['AtagsFlt' => 'atags'], ['AtagsFlt.id = AttachmentsAtags.atag_id'])
        ->where(['OR' => [
          'AtagsFlt.user_id IS' => null,
          'AtagsFlt.user_id'    => $userId,
        ]]);
    } else {
      $base->innerJoin(['AtagsFlt' => 'atags'], ['AtagsFlt.id = AttachmentsAtags.atag_id'])
        ->where(['AtagsFlt.user_id IS' => null]);
    }

    if ($type !== '' && $type !== 'all') {
      $base->where(['Attachments.type' => $type]);
    }
    if ($q !== '') {
      $base->where([
        'OR' => [
          'Attachments.name LIKE'        => '%' . $q . '%',
          'Attachments.title LIKE'       => '%' . $q . '%',
          'Attachments.description LIKE' => '%' . $q . '%',
        ],
      ]);
    }
    if ($date !== '') {
      $parts = array_map('trim', explode(',', $date));
      if (count($parts) === 2) {
        $base->where([
          'Attachments.date >=' => $parts[0] . ' 00:00:00',
          'Attachments.date <=' => $parts[1] . ' 23:59:59',
        ]);
      } elseif (count($parts) === 1) {
        $base->where(['Attachments.date >=' => $parts[0] . ' 00:00:00']);
      }
    }
    // If atags are already active, restrict to attachments having ALL of
    // them. We compute the matching attachment_ids first (single query on
    // the pivot, GROUP BY + HAVING for the AND-of-tags rule), then inject
    // the list as a WHERE IN. Avoids the brittle subquery-via-Cake-Query
    // pattern that cross-joined when feeding a Query as a WHERE value.
    $activeSlugs = array_values(array_filter(array_map('trim', explode(',', $atagsCsv)), fn($s) => $s !== ''));
    if (!empty($activeSlugs)) {
      $activeIds = $this->Atags->find()
        ->select(['id'])
        ->where(['Atags.slug IN' => $activeSlugs])
        ->all()
        ->extract('id')
        ->toList();
      if (empty($activeIds)) {
        // unknown slugs → empty result
        $base->where(['1 =' => 0]);
      } else {
        $junction = $this->Atags->getAssociation('Attachments')->junction();
        $junctionQ = $junction->find();
        // count(activeIds) is computed in PHP (int) so safe to inline; the
        // alternative `having([... = :n], ...)` triggers QueryExpression
        // edge cases on this CakePHP version.
        $n = count($activeIds);
        $matchingIds = $junctionQ
          ->select(['attachment_id'])
          ->where(['atag_id IN' => $activeIds])
          ->groupBy(['attachment_id'])
          ->having($junctionQ->newExpr("COUNT(DISTINCT atag_id) = {$n}"))
          ->all()
          ->extract('attachment_id')
          ->toList();
        if (empty($matchingIds)) {
          $base->where(['1 =' => 0]);
        } else {
          $base->where(['Attachments.id IN' => $matchingIds]);
        }
      }
    }

    $rows = $base->disableHydration()->all()->toList();
    $counts = [];
    foreach ($rows as $r) {
      $counts[(int)$r['atag_id']] = (int)$r['count'];
    }

    $this->set(['counts' => $counts]);
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['counts']);
  }

  // ---------------------------------------------------------------------
  // Per-user favourites
  //
  // Each user owns one personal atag in the "Sélection" type. The pair
  // (atag_id, attachment_id) in `attachments_atags` is the persisted
  // favourite. Lazy-creates the user's atag on first call so there's no
  // signup migration to run.
  // ---------------------------------------------------------------------

  protected const FAVORITES_TYPE_SLUG = 'selection';

  /**
   * Lookup or create the calling user's personal favourites atag.
   * @throws \Cake\Http\Exception\UnauthorizedException
   */
  protected function ensureUserFavoritesAtag(): \Cake\Datasource\EntityInterface
  {
    $userId = $this->currentUserId();
    if ($userId === null) {
      throw new \Cake\Http\Exception\UnauthorizedException();
    }
    $existing = $this->Atags->find()
      ->where([
        'Atags.user_id' => $userId,
        'Atags.atag_type_id IN' => $this->Atags->AtagTypes->find()
          ->select(['id'])
          ->where(['AtagTypes.slug' => self::FAVORITES_TYPE_SLUG]),
      ])
      ->first();
    if ($existing !== null) {
      return $existing;
    }
    $type = $this->Atags->AtagTypes->find()
      ->where(['AtagTypes.slug' => self::FAVORITES_TYPE_SLUG])
      ->first();
    if ($type === null) {
      throw new \RuntimeException('Atag type "' . self::FAVORITES_TYPE_SLUG . '" not configured.');
    }
    // user_id-suffixed slug keeps the column unique while staying readable.
    $shortId = substr(str_replace('-', '', $userId), 0, 8);
    $entity = $this->Atags->newEntity([
      'name' => 'Favoris',
      'slug' => 'favoris-' . $shortId,
      'atag_type_id' => $type->id,
      'user_id' => $userId,
    ]);
    if (!$this->Atags->save($entity)) {
      throw new \RuntimeException('Could not create favourites atag: ' . json_encode($entity->getErrors()));
    }
    return $entity;
  }

  /**
   * GET /attachment/favorites/me — return the caller's favourites state.
   * Lazy-creates the underlying atag on first call.
   *
   * Response: { atag_id, attachment_ids, count }
   */
  public function myFavorites()
  {
    $atag = $this->ensureUserFavoritesAtag();
    $junction = $this->Atags->getAssociation('Attachments')->junction();
    $rows = $junction->find()
      ->select(['attachment_id'])
      ->where(['atag_id' => $atag->id])
      ->disableHydration()
      ->all()
      ->toList();
    $ids = array_column($rows, 'attachment_id');
    $this->set([
      'atag_id'        => $atag->id,
      'attachment_ids' => $ids,
      'count'          => count($ids),
    ]);
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['atag_id', 'attachment_ids', 'count']);
  }

  /**
   * POST /attachment/favorites/toggle — add or remove an attachment from
   * the caller's favourites. Body: { attachment_id }.
   * Response: { favorited: bool, count }
   */
  public function toggleFavorite()
  {
    if (!$this->getRequest()->is('post')) {
      throw new \Cake\Http\Exception\MethodNotAllowedException();
    }
    $atag = $this->ensureUserFavoritesAtag();
    $aid = (string)$this->getRequest()->getData('attachment_id', '');
    if ($aid === '') {
      throw new \Cake\Http\Exception\BadRequestException('attachment_id required');
    }
    $junction = $this->Atags->getAssociation('Attachments')->junction();
    $existing = $junction->find()
      ->where(['atag_id' => $atag->id, 'attachment_id' => $aid])
      ->first();
    if ($existing) {
      $junction->delete($existing);
      $favorited = false;
    } else {
      $row = $junction->newEntity(['atag_id' => $atag->id, 'attachment_id' => $aid]);
      $junction->save($row);
      $favorited = true;
    }
    $count = $junction->find()->where(['atag_id' => $atag->id])->count();
    $this->set([
      'favorited' => $favorited,
      'count'     => $count,
      'atag_id'   => $atag->id,
    ]);
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['favorited', 'count', 'atag_id']);
  }
}
