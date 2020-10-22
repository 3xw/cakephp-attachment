<?php
namespace Attachment\Model\Filter\Restriction;

use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;

class TagRestricted extends BaseRestriction
{
  public static function process(Query $query, $atags )
  {
    if(empty($atags)) return;
    if(!is_array($atags)) $atags = [$atags];

    // subquery
    $subquery = TableRegistry::getTableLocator()
    ->get('AttachmentsAtags')
    ->find()
    ->select(['attachment_id'])
    ->innerJoin(['Atags' => 'atags'],['Atags.id = AttachmentsAtags.atag_id'])
    ->group(['attachment_id']);

    foreach($atags as $tag )
    {
      $subquery->having([
        'OR' => [
          "SUM(Atags.name = '$tag')",
          "SUM(Atags.slug = '".Text::slug($tag,'-')."')"
        ]
      ]);
    }

    $query->where(function ($exp, $q) use ($subquery){
      return $exp->in('Attachments.id', $subquery);
    });
  }
}
