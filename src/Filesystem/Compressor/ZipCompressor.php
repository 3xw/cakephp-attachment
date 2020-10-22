<?php
namespace Attachment\Filesystem\Compressor;

use Attachment\Model\Entity\Aarchive;
use Cake\Event\Event;
use Attachment\Filesystem\ProfileRegistry;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Zend\Diactoros\UploadedFile;
use Cake\Http\Exception\BadRequestException;

class ZipCompressor extends BaseCompressor
{
  protected $_defaultConfig = [
    'profile' => 'default',
    'maxInputSize' => 1000, // IN MB
    'maxFiles' => 40,
    'allowedTypes' => '*/*' // OR array ['image/*','application/pdf']
  ];

  public $path;
  public $name;

  protected function createArchive()
  {
    // zip time
    $this->name = uniqid('archive-', true).'.zip';
    $this->path = sys_get_temp_dir().$this->name;
    $zip = new Filesystem(new ZipArchiveAdapter($this->path));

    foreach($this->attachments as $attachment)
    {
      // profile
      $src = ProfileRegistry::retrieve($attachment->profile);

      // check
      if(!$src->has($attachment->path)) continue;

      // resolve dest ...
      $name = strtolower( time() . '_' . preg_replace('/[^a-z0-9_.]/i', '', $attachment->name) );
      if($attachment->type == 'embed') $name .= '.html';

      // copy file
      $zip->write($name, $attachment->type == 'embed'? $attachment->embed: $src->read($attachment->path));
    }

    // delete zip file !!
    $zipPath = $this->path;
    register_shutdown_function(function() use($zipPath) { unlink($zipPath); });
  }

  protected function moveArchive(Aarchive $entity)
  {
    $server = ProfileRegistry::retrieve($this->getConfig('profile'));
    $server->store($this->path, $this->name, false, 'public', 'application/zip');
    $attchment = $this->Attachments->newEntity([
      'path' => $this->name,
      'profile' => $this->getConfig('profile'),
      'type' => 'application',
      'subtype' => 'zip',
      'name' => $this->name,
      'size' => filesize($this->path),
      'md5' => md5($this->name),
      'path' => $this->name,
      'tile' => $this->name,
      'date' => date('Y-m-d H:i:s')
    ]);

    if(!$attchment = $this->Attachments->save($attchment)) throw new BadRequestException("Unable to create attachment");

    $entity->set('attachment_id', $attchment->id);
    $entity->set('state', 'COMPLETED');
  }

  public function compress(Aarchive $entity): bool
  {
    $this->createArchive();
    $this->moveArchive($entity);
    return true;
  }
}
