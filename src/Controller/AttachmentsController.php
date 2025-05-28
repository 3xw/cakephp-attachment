<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Event\Event;
use Crud\Event\Subject;
use Cake\Core\Configure;
use Cake\Http\Exception\UnauthorizedException;

/**
* Attachments Controller
*
* @property \Attachment\Model\Table\AttachmentsTable $Attachments
*/
class AttachmentsController extends AppController
{

  use \Crud\Controller\ControllerTrait;

  public $paginate = [
    'page' => 1,
    'limit' => 60,
    'maxLimit' => 50000,
    'order' => [
      'Attachments.created' => 'DESC'
    ],
    'sortWhitelist' => [
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

  public function index()
  {
    // security first !! be sure to restrict index with coresonding session settings!
    if(empty($this->request->getQuery('uuid'))) throw new UnauthorizedException(__d('Trois/Attachment','Missing uuid'));
    $this->Crud->on('beforePaginate', function (Event $event) {
      if(!empty(Configure::read('Trois/Attachment.browse.user_filter_tag_types'))){
        $this->loadModel('Users');
        $id = $this->getRequest()->getSession()->read('Auth')->id;
        $user = $this->Users->get($id, ['contain' => ['Atags']]);
        $tagsIds = [];
        if(!empty($user->atags))
        {
          foreach($user->atags as $tag) $tagsIds[] = $tag['id'];
        }
        if(!empty($tagsIds)){
          $event->getSubject()->query
            ->contain(['Atags'])
            ->matching('Atags', function ($q) use ($tagsIds) {
              return $q->where(['Atags.id IN' => $tagsIds]);
          });
        }
      }
    });

    return $this->Crud->execute();
  }

  public function view($id)
  {
    $this->Crud->on('beforeFind', function(\Cake\Event\Event $event) {
        $event->getSubject()->query->contain(['Aarchives']);
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

}
