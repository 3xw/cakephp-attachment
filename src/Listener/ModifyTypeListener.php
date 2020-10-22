<?php
namespace Trois\Attachment\Listener;

use Cake\Event\Event;

class ModifyTypeListener extends BaseListener
{
  protected $_defaultConfig = [
    'type' => 'transit',
    'subtype' => 'youtube',
    'conditions' => [
      'type' => 'video' // or directly a callable: funciton($entity){}
    ],
    'type' => 'OR' // AND
  ];

  public function respond(Event $event)
  {
    $conditions = $this->getConfig('conditions');
    $type = $this->getConfig('type');
    $entity = $event->getSubject()->entity;

    // test
    $changeValue = true;
    foreach($conditions as $key => $condition)
    {
      $bool = false;
      if(is_numeric($key)) $bool = (boolean) $this->getValueOrCallable($condition, $entity);
      else $bool = $entity->get($key) == $condition;

      if($type == 'OR')
      {
        $changeValue = $bool;
        if($changeValue) break;
      }
      else $changeValue = $changeValue && $bool;
    }

    // nothing to do here
    if(!$changeValue) return;

    //change types
    $entity
    ->set('type', $this->getConfig('type'))
    ->set('subtype', $this->getConfig('subtype'));
  }

  protected function getValueOrCallable($value, $arg)
  {
    if(is_callable($value)) return call_user_func($value, $arg);
    else return $value;
  }
}
