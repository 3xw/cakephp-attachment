<?php
namespace Attachment\Model\Filter;

use Search\Model\Filter\Base;
use Cake\Core\Configure;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

class Filters extends Base
{
  public function process():bool
  {
    // retrive data
    $value = $this->getArgs()[$this->getConfig('name')];
    if(empty($value)) return true;
    $filters = strpos($value, ',') !== false? explode(',', $value): [$value];

    $conditions = ['OR' => []];
    foreach($filters as $filter)
    {
      switch($filter)
      {
        case 'horizontal':
        array_push($conditions['OR'], ['Attachments.width > Attachments.height']);
        break;
        case 'vertical':
        array_push($conditions['OR'], ['Attachments.width < Attachments.height']);
        break;
        case 'square':
        array_push($conditions['OR'], ['Attachments.width = Attachments.height']);
        break;
      }
    }
    $this->getQuery()->where($conditions);

    return true;
  }
}
