<?php
namespace Trois\Attachment\ORM\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Exception;
use Cake\Utility\Text;
use Cake\Datasource\EntityInterface;
use Cake\Http\Session;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\Query;

use Cake\Log\Log;

use Cake\ORM\TableRegistry;

/**
 * Storage behavior
 */
class UserATagsBehavior extends Behavior
{
    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        if(!empty(Configure::read('Trois/Attachment.browse.user_filter_tag_types'))){
            $query->contain(['Atags']);
        }
    }
}
