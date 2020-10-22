<?php
namespace Attachment\ORM\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\Http\Session;
use Cake\ORM\Behavior;
use Cake\Routing\Router;

class UserIDBehavior extends Behavior
{
  protected $_defaultConfig = [
    'user_id' => 'user_id'
  ];

  public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
  {
    $identity = Router::getRequest()->getAttribute('identity');
    $identity = $identity ?? [];
    $loggedUserId = $identity['id'] ?? null;
    $data[$this->getConfig('user_id')] = $loggedUserId;
  }
}
