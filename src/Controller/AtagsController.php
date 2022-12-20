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
    if(empty($this->request->getQuery('uuid'))) throw new UnauthorizedException(__d('Trois/Attachment','Missing uuid'));

    $this->Crud->on('beforePaginate', function(Event $event)
    {
      $query = $event->getSubject()->query->contain(['AtagTypes']);

      if(Configure::read('Trois/Attachment.browse.filter_tags')){
        $type = $this->request->getQuery('type') == '' ? 'all' : explode('/',$this->request->getQuery('type'))[0];
        if($type == 'all'){
          $query->matching('Attachments')->group(['Atags.id']);
        }else {
          $query->matching('Attachments', function ($q) {
            return $q->where(['Attachments.type' => explode('/', $this->request->getQuery('type'))[0]]);
          })->group(['Atags.id']);
        }
      }
      if(Configure::read('Trois/Attachment.translate'))
      {
        $event->getSubject()->query->find('translations');
      }
    });

    return $this->Crud->execute();
  }
}
