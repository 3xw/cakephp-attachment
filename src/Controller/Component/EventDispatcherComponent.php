<?php
namespace Trois\Attachment\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use Crud\Event\Subject;
use Cake\Core\Configure;

class EventDispatcherComponent extends Component
{
  public $Crud = null;

  protected $_request = null;
  protected $_uuid = null;
  protected array $_defaultConfig = [
    'events' => [
      'beforeFilter',
      'startup',
      'beforeDelete',
      'afterDelete',
      'beforeFind',
      'afterFind',
      'beforeSave',
      'afterSave',
      'beforePaginate',
      'afterPaginate',
      'beforeRedirect',
      'beforeRender',
      'recordNotFound',
      'setFlash'
    ]
  ];

  public function __construct(ComponentRegistry $collection, $config = [])
  {
    parent::__construct($collection, $config);
    $this->Crud = $this->_registry->getController()->Crud;
    $this->_setEventListeners();
  }

  public function initialize(array $config):void
  {
    // setup
    parent::initialize($config);
    $this->_request = $this->_registry->getController()->getRequest();

    // retrieve listeners
    $listeners = Configure::read('Trois/Attachment.listeners');
    $uuid = $this->_request->getQuery('uuid')? $this->_request->getQuery('uuid'): $this->_request->getData('uuid');

    if($uuid)
    {
      if($sessionListeners = $this->_request->getSession()->read('Trois/Attachment.'.$uuid.'.listeners'))
      {
        $listeners = array_merge($listeners, $sessionListeners);
      }
    }

    // set listners
    $this->setConfig('listeners', $listeners);
  }

  protected function _setEventListeners()
  {
    foreach($this->getConfig('events') as $event) $this->Crud->on($event, [$this, 'respond']);
  }

  public function respond(Event $event)
  {
    // check if exists
    $name = ltrim(strstr($event->getName(),'.'),'.');
    if(!array_key_exists($name,$this->getConfig('listeners'))) return;

    // add request to event
    $subject = clone $event->getSubject();
    $subject->request = $this->_request;
    $listeners = $this->getConfig('listeners')[$name];

    // exec listeners
    foreach($listeners as $key => $value)
    {
      $config = is_array($value)? $value: [];
      $listener = is_array($value)? $key: $value;
      (new $listener($config))->respond(new Event($name, $subject));
    }
  }
}
