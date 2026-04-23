<?php
namespace Trois\Attachment\Model\Filter;

use Search\Model\Filter\Base;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Utility\Hash;

class SessionControl extends Base
{
  protected array $_defaultConfig = [
    'tag_restricted' => 'atags',
    'tag_or_restricted' => 'atags',
    'types_restricted' => 'types'
  ];

  public function process():bool
  {
    $uuid = $this->getArgs()[$this->getConfig('name')] ?? null;
    if(empty($uuid)) throw new UnauthorizedException(__d('Trois/Attachment','Missing uuid'));

    // Stateless API (v6) : no server session → no restrictions to apply,
    // authorization is handled upstream by the host app's policy layer.
    // Keep the legacy behaviour whenever a session entry does exist.
    $s = (new Session())->read('Trois/Attachment.'.$uuid);
    if (empty($s)) return true;

    if(!empty($s['restrictions']))
    {
      foreach($s['restrictions'] as $restriction)
      {
        $class = Inflector::camelize($restriction);
        $class = 'Trois\Attachment\Model\Filter\Restriction\\' . $class;
        if (class_exists($class)) $class::process($this->getQuery(), Hash::extract($s, $restriction));
      }
    }

    return true;
  }
}
