<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Event\Event;
use Cake\Core\Configure;

class AtagsController extends AppController
{
  use \Crud\Controller\ControllerTrait;

  public $paginate = [
        'limit' => 100000,
        'order' => [
            'Atags.name' => 'ASC'
        ]
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
    // security first !!
    if(empty($this->request->getQuery('uuid'))) throw new UnauthorizedException(__d('Attachment','Missing uuid'));

    $this->Crud->on('beforePaginate', function(Event $event)
    {
      $event->getSubject()->query->contain(['AtagTypes']);
      if(Configure::read('Attachment.translate'))
      {
        $event->getSubject()->query->find('translations');
      }
    });

    return $this->Crud->execute();
  }
}
