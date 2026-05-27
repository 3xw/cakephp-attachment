<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Trois\Attachment\Filesystem\ProfileRegistry;
use App\Service\ThumbnailSigner;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Crud\Event\Subject;
use Cake\Http\Exception\UnauthorizedException;

/**
* Attachments Controller
*
* @property \Attachment\Model\Table\AttachmentsTable $Attachments
*/
class AttachmentsController extends AppController
{

  use \Crud\Controller\ControllerTrait;

  public array $paginate = [
    'page' => 1,
    'limit' => 60,
    'maxLimit' => 50000,
    'order' => [
      'Attachments.created' => 'DESC'
    ],
    'sortableFields' => [
      'name', 'created', 'type', 'subtype', 'date'
    ]
  ];

  public function initialize():void
  {
    parent::initialize();

    $this->loadComponent('Crud.Crud', [
      'actions' => [
        'index' => [
          'className' => 'Crud.Index',
          'relatedModels' => ['Atags','Aarchives']
        ],
        'view' => [
          'className' => 'Crud.View',
        ],
        'add' =>[
          'className' => 'Crud.Add',
          'api.success.data.entity' => ['id','profile','path','type','subtype','name','size','fullpath', 'date'],
          'api.error.exception' => [
            'type' => 'validate',
            'class' => 'Trois\Attachment\Crud\Error\Exception\ValidationException'
          ],
        ],
        'edit' => [
          'className' => 'Crud.Edit',
          'relatedModels' => ['Atags']
        ],
        'editAll' => [
          'className' => 'Trois\Attachment\Crud\Action\Bulk\EditAction',
          'relatedModels' => ['Atags']
        ],
        'delete' => [
          'className' => 'Trois\Attachment\Crud\Action\DeleteAction',
        ],
        'deleteAll' => [
          'className' => 'Trois\Attachment\Crud\Action\Bulk\DeleteAction',
        ],
        'getSize' => [
          'className' => 'Trois\Attachment\Crud\Action\GetSizeAction',
        ]
      ],
      'listeners' => [
        //'CrudCache',
        'Crud.Api',
        'Crud.RelatedModels',
        'Crud.ApiPagination',
        'Crud.ApiQueryLog',
        'Crud.Search'
      ]
    ]);

    $this->loadComponent('Search.Search', [
      'actions' => ['getSize']
    ]);

    $this->loadComponent('Trois/Attachment.EventDispatcher');
  }

  /**
   * Identity of the caller, read from the Authentication middleware. Null on
   * unauthenticated requests (which are typically blocked upstream by RBAC
   * but we don't want to assume it here).
   */
  protected function _identity(): mixed
  {
    return $this->getRequest()->getAttribute('identity');
  }

  /**
   * Pipe the identity through `applyOptions` so the `ScopedBrowsingBehavior`
   * on AttachmentsTable can apply the per-user atag filter on every query
   * that originates from this controller. Use everywhere we hit the
   * Attachments table — `index`, `view`, `edit`, `bulkEdit`, `bulkDelete`.
   *
   * Centralising this avoids the legacy bug where one action forgot the
   * filter and exposed the full library.
   */
  protected function _applyScope(\Cake\ORM\Query\SelectQuery $query): \Cake\ORM\Query\SelectQuery
  {
    return $query->applyOptions(['identity' => $this->_identity()]);
  }

  /**
   * Inject signed thumbnail URLs into an attachment entity so the frontend
   * can render <img> tags that pass nginx's HMAC gate without an extra API
   * round-trip. Three dims because the UI uses three:
   *   - list_thumb_*  → w80h60   (table view)
   *   - thumb_*       → w400h300 (grid)
   *   - preview_*     → w1200    (lightbox / video poster)
   * One MD5 each, negligible cost. (WGRC-805)
   *
   * Mutates the entity in place. Returns it for chaining convenience.
   */
  protected function _attachThumbUrls(EntityInterface $entity): EntityInterface
  {
    $profile = (string)($entity->profile ?? 'default');
    $path = (string)($entity->path ?? '');
    if ($path === '') {
      return $entity;
    }
    $signer = ThumbnailSigner::fromEnv();
    $list = $signer->sign($profile, 'w80h60', $path);
    $thumb = $signer->sign($profile, 'w400h300', $path);
    $preview = $signer->sign($profile, 'w1200', $path);
    $entity->set('list_thumb_url', $list['url'], ['guard' => false]);
    $entity->set('list_thumb_sig', $list['sig'], ['guard' => false]);
    $entity->set('list_thumb_exp', $list['exp'], ['guard' => false]);
    $entity->set('thumb_url', $thumb['url'], ['guard' => false]);
    $entity->set('thumb_sig', $thumb['sig'], ['guard' => false]);
    $entity->set('thumb_exp', $thumb['exp'], ['guard' => false]);
    $entity->set('preview_url', $preview['url'], ['guard' => false]);
    $entity->set('preview_sig', $preview['sig'], ['guard' => false]);
    $entity->set('preview_exp', $preview['exp'], ['guard' => false]);

    return $entity;
  }

  public function index()
  {
    // Optional `?ids=a,b,c` filter — lets the frontend fetch a specific
    // selection across pages (used by the edit drawer and "Voir la
    // sélection" feature). Bounded by the bulk-edit cap upstream.
    $idsParam = (string)$this->getRequest()->getQuery('ids', '');
    $idsFilter = array_values(array_filter(
      array_map('trim', explode(',', $idsParam)),
      fn($v) => $v !== ''
    ));

    $identity = $this->_identity();
    $this->Crud->on('beforePaginate', function (Event $event) use ($idsFilter, $identity) {
      $query = $event->getSubject()->query;
      if (!empty($idsFilter)) {
        $query->where(['Attachments.id IN' => $idsFilter]);
      }
      // Pass identity to the ScopedBrowsingBehavior. The behavior handles
      // the no-op cases (no identity, admin/superuser, feature off, user
      // has no scope atag) so we don't need to branch here. (WGRC-803)
      $query->applyOptions(['identity' => $identity]);
    });

    // Decorate every paginated entity with the signed thumbnail URLs the
    // frontend needs. Runs on `afterPaginate` so the signed fields land
    // in the iteration the Crud Api listener serialises. (WGRC-805)
    $this->Crud->on('afterPaginate', function (Event $event) {
      $entities = $event->getSubject()->entities ?? null;
      if ($entities === null) {
        return;
      }
      foreach ($entities as $entity) {
        if ($entity instanceof EntityInterface) {
          $this->_attachThumbUrls($entity);
        }
      }
    });

    return $this->Crud->execute();
  }

  public function view($id)
  {
    $identity = $this->_identity();
    $this->Crud->on('beforeFind', function (Event $event) use ($identity) {
        $event->getSubject()->query
          ->contain(['Aarchives'])
          ->applyOptions(['identity' => $identity]);
    });
    $this->Crud->on('afterFind', function (Event $event) {
      $entity = $event->getSubject()->entity ?? null;
      if ($entity instanceof EntityInterface) {
        $this->_attachThumbUrls($entity);
      }
    });
    return $this->Crud->execute();
  }

  /**
   * Edit a single attachment
   * Supports FormData with file replacement
   *
   * @param string|null $id Attachment id
   * @return \Cake\Http\Response|null
   */
  public function edit($id = null)
  {
    $identity = $this->_identity();
    $this->Crud->on('beforeFind', function (Event $event) use ($identity) {
        $event->getSubject()->query
          ->contain(['Atags'])
          ->applyOptions(['identity' => $identity]);
    });
    return $this->Crud->execute();
  }

  public function source($path)
  {

    $attachment = $this->Attachments->find()
    ->where(['Attachments.path' => $path])
    ->firstOrFail();

    $sourceFilePath = WWW_ROOT . 'source' . DS . $attachment->path;
    $profile = ProfileRegistry::retrieve($attachment->profile);
    $content = $profile->read($attachment->path);

    file_put_contents($sourceFilePath, $content);

    $this->response = $this->response
      ->withType($attachment->type . '/' . $attachment->subtype)
      ->withStringBody($content);

    return $this->response;

  }

  /**
   * Bulk unlink atags from a list of attachments.
   *
   * Body : { "attachment_ids": int[], "atag_ids": int[] }
   * Returns : JSON { removed: [{attachment_id, atag_id}], requested: {...} }
   *
   * Authorisation : left to the host app's policy layer — this endpoint only
   * touches the pivot, never the Attachment entities themselves. Wrap with an
   * RBAC rule in `config/permissions.php` if scoped access is required.
   */
  public function deleteAtags()
  {
    $request = $this->getRequest();
    if (!in_array($request->getMethod(), ['DELETE', 'POST'], true)) {
      throw new \Cake\Http\Exception\MethodNotAllowedException();
    }

    $data = $request->getParsedBody() ?: [];
    $attachmentIds = $data['attachment_ids'] ?? [];
    $atagIds = $data['atag_ids'] ?? [];

    if (!is_array($attachmentIds) || !is_array($atagIds)) {
      throw new \Cake\Http\Exception\BadRequestException('attachment_ids and atag_ids must be arrays');
    }

    $Attachments = $this->fetchTable('Trois/Attachment.Attachments');
    // Narrow to attachments the caller can actually browse (WGRC-803).
    $attachmentIds = array_values(array_filter($attachmentIds, 'is_string'));
    if (!empty($attachmentIds)) {
      $attachmentIds = $this->_applyScope($Attachments->find())
        ->select(['Attachments.id'])
        ->where(['Attachments.id IN' => $attachmentIds])
        ->disableHydration()
        ->all()
        ->extract('id')
        ->toList();
    }

    $removed = $Attachments->unlinkAtags($attachmentIds, $atagIds);

    $this->set([
      'removed' => $removed,
      'requested' => [
        'attachment_ids' => array_values(array_map('intval', $attachmentIds)),
        'atag_ids' => array_values(array_map('intval', $atagIds)),
      ],
    ]);
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['removed', 'requested']);
  }

  /**
   * Bulk edit: update props and/or add/remove tag links on 1..N attachments
   * in a single request.
   *
   * Body:
   *   {
   *     "ids": [uuid, uuid, ...],
   *     "set": { "title": "...", "description": "...", "author": "...",
   *              "copyright": "...", "date": "2026-04-24" },  // optional, partial
   *     "tags_add": [atag_id, atag_id, ...],     // optional
   *     "tags_remove": [atag_id, atag_id, ...]   // optional
   *   }
   *
   * Only fields explicitly present in `set` are updated — omitted props stay
   * untouched (so tri-state "leave as-is" on the UI never triggers a silent
   * overwrite). Tag operations are symmetric: `tags_add` is INSERT IGNORE,
   * `tags_remove` is a straight DELETE on the pivot.
   */
  public function bulkEdit()
  {
    $request = $this->getRequest();
    if (!in_array($request->getMethod(), ['PATCH', 'POST'], true)) {
      throw new \Cake\Http\Exception\MethodNotAllowedException();
    }

    $data = $request->getParsedBody() ?: [];
    $ids = $data['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
      throw new \Cake\Http\Exception\BadRequestException('ids must be a non-empty array');
    }
    $ids = array_values(array_filter($ids, 'is_string'));
    if (empty($ids)) {
      throw new \Cake\Http\Exception\BadRequestException('no valid ids');
    }

    // Whitelist of props that can be bulk-edited. Never expose `path`,
    // `md5`, `size`, `profile`, `user_id` etc. — those are owned by the
    // upload pipeline.
    $allowed = ['title', 'description', 'author', 'copyright', 'date'];
    $set = [];
    foreach ($allowed as $k) {
      if (array_key_exists($k, (array)($data['set'] ?? []))) {
        $v = $data['set'][$k];
        // Normalise date: frontend sends "YYYY-MM-DD", store at midnight.
        if ($k === 'date' && $v !== null && $v !== '') {
          $v = (string)$v . ' 00:00:00';
        }
        $set[$k] = $v;
      }
    }

    $tagsAdd    = array_values(array_map('intval', (array)($data['tags_add']    ?? [])));
    $tagsRemove = array_values(array_map('intval', (array)($data['tags_remove'] ?? [])));

    $Attachments = $this->fetchTable('Trois/Attachment.Attachments');

    // Narrow `$ids` to what the caller is actually allowed to see, so a
    // scoped user can never bulk-mutate rows outside their atag scope
    // (defence-in-depth on top of frontend filtering). (WGRC-803)
    $scopedIds = $this->_applyScope($Attachments->find())
      ->select(['Attachments.id'])
      ->where(['Attachments.id IN' => $ids])
      ->disableHydration()
      ->all()
      ->extract('id')
      ->toList();
    $ids = array_values(array_unique($scopedIds));
    if (empty($ids)) {
      $this->set(['updated' => 0, 'linked' => 0, 'unlinked' => []]);
      $this->viewBuilder()->setClassName('Json');
      $this->viewBuilder()->setOption('serialize', ['updated', 'linked', 'unlinked']);
      return;
    }

    $updatedProps = 0;
    if (!empty($set)) {
      $set['modified'] = date('Y-m-d H:i:s');
      $updatedProps = $Attachments->updateAll($set, ['id IN' => $ids]);
    }

    $linksAdded = 0;
    if (!empty($tagsAdd)) {
      $linksAdded = $Attachments->linkAtags($ids, $tagsAdd);
    }
    $linksRemoved = [];
    if (!empty($tagsRemove)) {
      $linksRemoved = $Attachments->unlinkAtags($ids, $tagsRemove);
    }

    $this->set([
      'updated' => $updatedProps,
      'linked' => $linksAdded,
      'unlinked' => $linksRemoved,
    ]);
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['updated', 'linked', 'unlinked']);
  }

  /**
   * Bulk delete: delete N attachments in one request. Goes through
   * Table::delete() per entity so FlyBehavior/AarchiveBehavior fire and the
   * S3 originals + thumbnails get cleaned up.
   *
   * Body: { "ids": [uuid, uuid, ...] }
   * Returns: { deleted: int, failed: [uuid, ...] }
   */
  public function bulkDelete()
  {
    $request = $this->getRequest();
    if (!in_array($request->getMethod(), ['DELETE', 'POST'], true)) {
      throw new \Cake\Http\Exception\MethodNotAllowedException();
    }

    $data = $request->getParsedBody() ?: [];
    $ids = $data['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
      throw new \Cake\Http\Exception\BadRequestException('ids must be a non-empty array');
    }
    $ids = array_values(array_filter($ids, 'is_string'));
    if (empty($ids)) {
      throw new \Cake\Http\Exception\BadRequestException('no valid ids');
    }

    $Attachments = $this->fetchTable('Trois/Attachment.Attachments');
    // Defence-in-depth: a scoped user can only delete the rows they're
    // allowed to see. Behavior is a no-op for admin/superuser. (WGRC-803)
    $entities = $this->_applyScope($Attachments->find())
      ->where(['Attachments.id IN' => $ids])
      ->all();

    $deleted = 0;
    $failed = [];
    foreach ($entities as $entity) {
      try {
        if ($Attachments->delete($entity)) $deleted++;
        else $failed[] = $entity->id;
      } catch (\Throwable $e) {
        $failed[] = $entity->id;
      }
    }

    $this->set(compact('deleted', 'failed'));
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['deleted', 'failed']);
  }

}
