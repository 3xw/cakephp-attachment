<?php
namespace Attachment\Model\Filter\Restriction;

use Cake\ORM\Query;
use Cake\Utility\Text;
use Cake\Routing\Router;

class UserOrNoOneRestricted extends BaseRestriction
{
  public static function process(Query $query, $noArgs )
  {
    $identity = Router::getRequest()->getAttribute('identity');
    $userId = $identity? $identity->getIdentifier(): '';

    $query->where([
      'Attachments.user_id IN' => [$userId,'']
    ]);
  }
}
