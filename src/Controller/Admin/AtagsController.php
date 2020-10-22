<?php
declare(strict_types=1);

namespace Trois\Attachment\Controller\Admin;

use Trois\Attachment\Controller\AppController;

/**
* Atags Controller
*
* @property \Attachment\Model\Table\AtagsTable $Atags
*
* @method \Attachment\Model\Entity\Atag[] paginate($object = null, array $settings = [])
*/
class AtagsController extends AppController
{

  public $paginate = [
    'limit' => 200,
    'order' => [
      'Atags.atag_type_id' => 'ASC',
      'Atags.name' => 'ASC'
    ]
  ];

  public function initialize():void
  {
    parent::initialize();
    $this->loadComponent('Search.Prg', [
      'actions' => ['index']
    ]);
  }

  /**
  * Index method
  *
  * @return \Cake\Http\Response|void
  */
  public function index()
  {
    $query = $this->Atags->find('search', ['search' => $this->request->getQuery()])->contain(['AtagTypes', 'Users']);
    if (!empty($this->request->getQuery('q'))) {
      if (!$query->count()) {
        $this->Flash->error(__('No result.'));
      }else{
        $this->Flash->success($query->count()." ".__('result(s).'));
      }
      $this->set('q',$this->request->getQuery('q'));
    }
    $atags = $this->paginate($query);
    $this->set(compact('atags'));
  }

  /**
  * View method
  *
  * @param string|null $id Atag id.
  * @return \Cake\Http\Response|void
  * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
  */
  public function view($id = null)
  {
    $atag = $this->Atags->get($id, [
      'contain' => ['AtagTypes', 'Users', 'Attachments']
    ]);

    $this->set('atag', $atag);
  }

  /**
  * Add method
  *
  * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
  */
  public function add()
  {
    $atag = $this->Atags->newEmptyEntity();
    if ($this->request->is('post')) {
      $atag = $this->Atags->patchEntity($atag, $this->request->getData());
      if ($this->Atags->save($atag)) {
        $this->Flash->success(__('The atag has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The atag could not be saved. Please, try again.'));
    }
    $atagTypes = $this->Atags->AtagTypes->find('list', ['limit' => 200]);
    $this->set(compact('atag', 'atagTypes'));
  }

  /**
  * Edit method
  *
  * @param string|null $id Atag id.
  * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
  * @throws \Cake\Network\Exception\NotFoundException When record not found.
  */
  public function edit($id = null)
  {
    $atag = $this->Atags->get($id, [
      'contain' => ['Attachments']
    ]);
    if ($this->request->is(['patch', 'post', 'put'])) {
      $atag = $this->Atags->patchEntity($atag, $this->request->getData());
      if ($this->Atags->save($atag)) {
        $this->Flash->success(__('The atag has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The atag could not be saved. Please, try again.'));
    }
    $atagTypes = $this->Atags->AtagTypes->find('list', ['limit' => 200]);
    $this->set(compact('atag', 'atagTypes'));
  }

  /**
  * Delete method
  *
  * @param string|null $id Atag id.
  * @return \Cake\Http\Response|null Redirects to index.
  * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
  */
  public function delete($id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $atag = $this->Atags->get($id);
    if ($this->Atags->delete($atag)) {
      $this->Flash->success(__('The atag has been deleted.'));
    } else {
      $this->Flash->error(__('The atag could not be deleted. Please, try again.'));
    }

    return $this->redirect(['action' => 'index']);
  }
}
