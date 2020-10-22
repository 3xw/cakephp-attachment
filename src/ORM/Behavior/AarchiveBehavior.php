<?php
namespace Trois\Attachment\ORM\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior;

class AarchiveBehavior extends Behavior
{

  protected $_defaultConfig = [];

  public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if(!property_exists($entity, 'aarchive')) $entity->set('aarchive', $this->_table->Aarchives
      ->find()
      ->where(['id' => $entity->id])
      ->first()
    );

    if(!empty($entity->aarchive)) $this->Aarchives->delete($entity->aarchive);
  }
}
