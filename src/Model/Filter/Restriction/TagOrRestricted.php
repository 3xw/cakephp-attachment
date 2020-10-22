<?php
namespace Trois\Attachment\Model\Filter\Restriction;

use Cake\ORM\Query;
use Cake\Utility\Text;

class TagOrRestricted extends BaseRestriction
{
  public static function process(Query $query, $atags )
  {
    if(empty($atags)) return;
    if(!is_array($atags)) $atags = [$atags];

    $query->innerJoin(['AAtags' => 'attachments_atags'],['AAtags.attachment_id = Attachments.id']);
    $query->innerJoin(['Atags' => 'atags'],['Atags.id = AAtags.atag_id']);
    $query->group(['Attachments.id']);

    $conditions = [
      'OR' => [
        'Atags.name IN' => $atags,
        'Atags.slug IN' => array_map(function($val) { return Text::slug($val,'-'); }, $atags)
      ]
    ];
    $query->andWhere($conditions);
  }
}
