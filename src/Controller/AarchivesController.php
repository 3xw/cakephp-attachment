<?php
declare(strict_types=1);

namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Event\Event;
use Cake\Core\Configure;

class AarchivesController extends AppController
{
  use \Crud\Controller\ControllerTrait;

  public $paginate = [
    'page' => 1,
    'limit' => 20,
    'order' => [
      'Aarchives.created' => 'DESC'
    ]
  ];

  public function initialize():void
  {
    parent::initialize();

    $this->loadComponent('Crud.Crud', [
      'actions' => [
        'index' => [
          'className' => 'Crud.Index'
        ],
        'add' =>[
          'className' => 'Crud.Add'
        ],
        'view' =>[
          'className' => 'Crud.View'
        ],
        'delete' => [
          'className' => 'Trois\Attachment\Crud\Action\DeleteAction',
        ]
      ],
      'listeners' => [
        //'CrudCache',
        'Trois\Attachment\Crud\Listener\JsonApiListener',
        'Crud.RelatedModels',
        'Crud.ApiPagination',
        'Crud.ApiQueryLog',
      ]
    ]);

    $this->loadComponent('Trois/Attachment.EventDispatcher');
  }

  public function index()
  {
    // get user_id
    $identity = $this->getRequest()->getAttribute('identity');
    $identity = $identity ?? [];
    $loggedUserId = $identity['id'] ?? null;

    // find by user_id
    $this->Crud->on('beforePaginate', function($event) use($loggedUserId)
    {
      $event->getSubject()->query->where(['Aarchives.user_id' => $loggedUserId]);
    });

    return $this->Crud->execute();
  }

  public function add()
  {
    // checks
    $ids = $this->getRequest()->getData('aids');
    if(!$this->getRequest()->is('POST') || empty($ids) || !is_array($ids)) throw new BadRequestException('Need POST request with an aids array!');

    // compressor
    $compressor = Configure::read('Trois/Attachment.archives');
    $this->Crud->on('beforeSave', [$compressor, 'beforeSave']);
    $this->Crud->on('afterSave', [$compressor, 'afterSave']);

    // crud
    return $this->Crud->execute();
  }
}
