<?php
namespace Attachment\Controller;

use Attachment\Controller\AppController;
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
            'class' => 'Attachment\Crud\Error\Exception\ValidationException'
          ],
        ],
        'edit' => [
          'className' => 'Crud.Edit',
          'relatedModels' => ['Atags']
        ],
        'editAll' => [
          'className' => 'Attachment\Crud\Action\Bulk\EditAction',
          'relatedModels' => ['Atags']
        ],
        'delete' => [
          'className' => 'Attachment\Crud\Action\DeleteAction',
        ],
        'deleteAll' => [
          'className' => 'Attachment\Crud\Action\Bulk\DeleteAction',
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

    $this->loadComponent('Attachment.EventDispatcher');
  }

  public function index()
  {
    // security first !! be sure to restrict index with coresonding session settings!
    if(empty($this->request->getQuery('uuid'))) throw new UnauthorizedException(__d('Attachment','Missing uuid'));

    return $this->Crud->execute();
  }

  public function view($id)
  {
      $this->Crud->on('beforeFind', function(\Cake\Event\Event $event) {
          $event->getSubject()->query->contain(['Aarchives']);
      });
      return $this->Crud->execute();
  }
}
