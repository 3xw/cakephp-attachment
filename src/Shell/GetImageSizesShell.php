<?php
namespace Trois\Attachment\Shell;

use Cake\Core\Configure;
use Cake\Console\Shell;
use Trois\Attachment\Filesystem\FilesystemRegistry;

class GetImageSizesShell extends Shell
{
  private function _filesystem($profile)
  {
    return FilesystemRegistry::retrieve($profile);
  }

  public function main()
  {
    Configure::write('Attachment.profiles.shell_script',[
      'adapter' => 'League\Flysystem\Adapter\Local',
      'client' => new \League\Flysystem\Adapter\Local(WWW_ROOT.'../tmp'),
      'baseUrl' =>  ''
    ]);

    $this->loadModel('Attachments');
    $attachments = $this->Attachments
    ->find()
    ->select(['id','name','profile','path','type','subtype','size'])
    ->where([
      'type' => 'image',
      'subtype IN' => ['gif','png','jpeg']
    ])
    ->toArray();

    $this->info('extract '.count($attachments).' files');
    $count = 0;

    foreach($attachments as $attachment)
    {
      $this->out('processing file: '.$attachment->name);

      $profile = $attachment->profile;
      if(!Configure::check('Attachment.profiles.'.$profile)){ continue; }

      $contents = $this->_filesystem($profile)->read($attachment->path);
      $this->_filesystem('shell_script')->write($attachment->name,$contents);
      unset($contents);

      $image_info = getimagesize(WWW_ROOT.'../tmp/'.$attachment->name);
      $image_width = $image_info[0];
      $image_height = $image_info[1];
      $attachment->set('width', $image_width);
      $attachment->set('height', $image_height);

      $this->_filesystem('shell_script')->delete($attachment->name);

      if($this->Attachments->save($attachment))
      {
        $this->success('saved file: '.$attachment->name);
        $count++;
      }else{
        $this->err('error saving file: '.$attachment->name);
      }

    }

    $this->hr();
    $this->info($count.' files were updated on total'.count($attachments).'!');
  }
}
