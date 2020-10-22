<?php
namespace Attachment\Model\Filter;

use Search\Model\Filter\Base;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Utility\Hash;

class SessionControl extends Base
{
  protected $_defaultConfig = [
    'tag_restricted' => 'atags',
    'tag_or_restricted' => 'atags',
    'types_restricted' => 'types'
  ];

  public function process():bool
  {
    // security first
    $uuid = $this->getArgs()[$this->getConfig('name')];
    if(empty($uuid)) throw new UnauthorizedException(__d('Attachment','Missing uuid'));
    if(!$s = (new Session())->read('Attachment.'.$uuid)) throw new UnauthorizedException(__d('Attachment','Uuid is not matching any session settings'));

    if(!empty($s['restrictions']))
    {
      foreach($s['restrictions'] as $restriction)
      {
        $class = Inflector::camelize($restriction);
        $class = 'Attachment\Model\Filter\Restriction\\' . $class;
        if (class_exists($class)) $class::process($this->getQuery(), Hash::extract($s, $restriction));
      }
    }

    return true;
  }
}
