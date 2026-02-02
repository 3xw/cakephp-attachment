<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

class AtagsController extends AppController
{
  use \Crud\Controller\ControllerTrait;

  public array $paginate = [
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
}
