<?php
declare(strict_types=1);

namespace Attachment\Controller\Admin;

use Attachment\Controller\AppController;

/**
* AtagTypes Controller
*
* @property \Attachment\Model\Table\AtagTypesTable $AtagTypes
*
* @method \Attachment\Model\Entity\AtagType[] paginate($object = null, array $settings = [])
*/
class AtagTypesController extends AppController
{
  public $paginate = [
    'limit' => 100,
    'order' => [
      'AtagTypes.order' => 'ASC'
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
    $query = $this->AtagTypes->find('search', ['search' => $this->request->getQuery()])->contain([]);
    if (!empty($this->request->getQuery('q'))) {
      if (!$query->count()) {
        $this->Flash->error(__('No result.'));
      }else{
        $this->Flash->success($query->count()." ".__('result(s).'));
      }
      $this->set('q',$this->request->getQuery('q'));
    }
    $atagTypes = $this->paginate($query);
    $this->set(compact('atagTypes'));
  }

  /**
  * View method
  *
  * @param string|null $id Atag Type id.
  * @return \Cake\Http\Response|void
  * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
  */
  public function view($id = null)
  {
    $atagType = $this->AtagTypes->get($id, [
      'contain' => ['Atags']
    ]);

    $this->set('atagType', $atagType);
  }

  /**
  * Add method
  *
  * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
  */
  public function add()
  {
    $atagType = $this->AtagTypes->newEmptyEntity();
    if ($this->request->is('post')) {
      $atagType = $this->AtagTypes->patchEntity($atagType, $this->request->getData());
      if ($this->AtagTypes->save($atagType)) {
        $this->Flash->success(__('The atag type has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The atag type could not be saved. Please, try again.'));
    }
    $this->set(compact('atagType'));
  }

  /**
  * Edit method
  *
  * @param string|null $id Atag Type id.
  * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
  * @throws \Cake\Network\Exception\NotFoundException When record not found.
  */
  public function edit($id = null)
  {
    $atagType = $this->AtagTypes->get($id, [
      'contain' => []
    ]);
    if ($this->request->is(['patch', 'post', 'put'])) {
      $atagType = $this->AtagTypes->patchEntity($atagType, $this->request->getData());
      if ($this->AtagTypes->save($atagType)) {
        $this->Flash->success(__('The atag type has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The atag type could not be saved. Please, try again.'));
    }
    $this->set(compact('atagType'));
  }

  /**
  * Delete method
  *
  * @param string|null $id Atag Type id.
  * @return \Cake\Http\Response|null Redirects to index.
  * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
  */
  public function delete($id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $atagType = $this->AtagTypes->get($id);
    if ($this->AtagTypes->delete($atagType)) {
      $this->Flash->success(__('The atag type has been deleted.'));
    } else {
      $this->Flash->error(__('The atag type could not be deleted. Please, try again.'));
    }

    return $this->redirect(['action' => 'index']);
  }
}
