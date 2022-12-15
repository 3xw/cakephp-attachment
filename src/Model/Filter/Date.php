<?php
namespace Trois\Attachment\Model\Filter;

use Search\Model\Filter\Base;
use Cake\Core\Configure;

class Date extends Base
{
  public function process():bool
  {
    // retrive data
    $value = $this->getArgs()[$this->getConfig('name')];
    if(empty($value)) return true;
    $dates = explode(',', $value);
    if(count($dates) != 2 && count($dates) != 1) return true;

    if (count($dates) === 1) {
      try {
        $from = new \DateTime($dates[0]);        
        $to = new \DateTime();
      } catch (\Exception $e) {
        // silence is golden
        return true;
      }
      $this->getQuery()
        ->where(function ($exp, $q) use ($from, $to) {
          return $exp->between(Configure::read('Trois/Attachment.browse.search.dateField'), $from, $to);
        });

      return true;
    }else {
      try {
        $from = new \DateTime($dates[0]);
        $to = new \DateTime($dates[1]);
      } catch (\Exception $e) {
        // silence is golden
        return true;
      }
      $this->getQuery()
        ->where(function ($exp, $q) use ($from, $to) {
          return $exp->between(Configure::read('Trois/Attachment.browse.search.dateField'), $from, $to);
        });

      return true;
    }
  }
}
