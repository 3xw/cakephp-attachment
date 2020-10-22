<?php
namespace Trois\Attachment\Listener;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;

class BaseListener
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [  ];

  public function __construct($config = [])
  {
    $this->setConfig($config);
  }

  public function respond(Event $event){}
}
