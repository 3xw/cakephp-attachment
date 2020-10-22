<?php
namespace Trois\Attachment\Model\Filter\Restriction;

use Cake\ORM\Query;

class TypesRestricted extends BaseRestriction
{
  public static function process(Query $query, $mimes )
  {
    if(empty($mimes)) return;
    if(!is_array($mimes)) $mimes = [$mimes];

    $conditions = [
      'type' => [],
      'subtype' => []
    ];

    // loop mimes
    foreach($mimes as $mime)
    {
      // set what to do
      $mime = explode('/', $mime);
      $negative = preg_match("/\!/", $mime[0]);
      $logic = $negative? ' NOT IN ': ' IN ';
      $field = !empty($mime[1]) && $mime[1] != '*' ? 'subtype': 'type';
      $value = !empty($mime[1]) && $mime[1] != '*' ? $mime[1]: ($negative? substr($mime[0], 1): $mime[0]);

      // apply
      if(!array_key_exists($logic, $conditions[$field])) $conditions[$field][$logic] = [];
      $conditions[$field][$logic][] = $value;
    }

    $condition = '( ';

    // modify qeury
    foreach($conditions as $field => $c)
    {
      if(!empty($c)) foreach($c as $logic => $values)
      {
        $condition .= $field.$logic.sprintf("('%s')", implode("','", $values ));
        $condition .= preg_match("/\NOT/", $logic)? " AND ": " OR ";
      }
    }

    $condition = substr($condition,0, -4).')';
    $query->andWhere([$condition]);
  }
}
