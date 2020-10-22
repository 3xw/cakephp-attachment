<?php
namespace Trois\Attachment\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Log\Log;
use Trois\Attachment\Http\Cdn\BaseCdn;

class ClearTask extends Shell
{
  public function main($profile, ...$paths)
  {
    $cdn = Configure::read('Attachment.profiles.'.$profile.'.cdn');

    if(!$cdn) throw new \Exception("Attachment CDN configuration for profile: ".$profile." doesn't exists", 1);
    if(!$cdn instanceof BaseCdn) throw new \Exception("CDN is not an instance of BaseCdn for profile: ".$profile, 1);

    if($result = $cdn->clear($paths)) return $this->out($result);
    else $this->err($cdn->getError());
  }
}
