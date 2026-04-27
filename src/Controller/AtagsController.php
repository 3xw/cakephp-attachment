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
      'actions' => ['Crud.Index'],
      'listeners' => [
        //'CrudCache',
        'Crud.Api',
        //'Crud.ApiPagination',
        'Crud.ApiQueryLog',
        //'Crud.Search'
      ]
    ]);
  }

  public function index()
  {
    // uuid is the legacy session-control handle — optional in stateless API.
    $this->Crud->on('beforePaginate', function(Event $event)
    {
      $query = $event->getSubject()->query->contain(['AtagTypes']);

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
    // If atags are already active, restrict to attachments having ALL of them.
    // We resolve the slugs to IDs first (cheap; bounded by selection size).
    $activeSlugs = array_values(array_filter(array_map('trim', explode(',', $atagsCsv)), fn($s) => $s !== ''));
    if (!empty($activeSlugs)) {
      $activeIds = $this->Atags->find()
        ->select(['id'])
        ->where(['Atags.slug IN' => $activeSlugs])
        ->all()
        ->extract('id')
        ->toList();
      if (!empty($activeIds)) {
        $base->where(['Attachments.id IN' => $this->Atags->find()
          ->select(['attachment_id' => 'AA.attachment_id'])
          ->from(['AA' => 'attachments_atags'])
          ->where(['AA.atag_id IN' => $activeIds])
          ->groupBy(['AA.attachment_id'])
          ->having(['COUNT(DISTINCT AA.atag_id) =' => count($activeIds)]),
        ]);
      } else {
        // unknown slugs → empty result
        $base->where(['1 =' => 0]);
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
}
