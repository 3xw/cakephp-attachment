<?php
namespace Attachment\Model\Filter;

use Search\Model\Filter\Base;
use Cake\Core\Configure;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

class Restriction extends Base
{
  public function process():bool
  {
    // retrive data
    $value = $this->getArgs()[$this->getConfig('name')];
    if(empty($value)) return true;
    if(strpos($value, ',') !== false) $value = explode(',', $value);

    if(!empty($this->getConfig('restrictions')))
    {
      foreach($this->getConfig('restrictions') as $restriction)
      {
        $class = Inflector::camelize($restriction);
        $class = 'Attachment\Model\Filter\Restriction\\' . $class;
        if (class_exists($class)) $class::process($this->getQuery(), $value);
      }
    }

    return true;
  }
}
