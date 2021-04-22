<?php
namespace Trois\Attachment\Model\Filter\Restriction;

use Cake\ORM\Query;
use Cake\Utility\Text;

class ProfileRestricted extends BaseRestriction
{
  public static function process(Query $query, $profiles )
  {
    if(empty($profiles)) return;
    if(!is_array($profiles)) $profiles = [$profiles];

    // Check profile!
    $conditions = ['Attachments.profile IN' => $profiles];
    $query->andWhere($conditions);
  }
}
