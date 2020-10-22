<?php
declare(strict_types=1);

namespace Trois\Attachment\Filesystem;

use Trois\Attachment\Filesystem\Profile;
use Trois\Attachment\Model\Entity\Attachment;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Cake\Http\Exception\NotFoundException;
use Trois\Attachment\Filesystem\ProfileRegistry;

class Downloader
{
  public $profile;

  function __construct($profileName = 'sys_temp')
  {
    $this->profile = ProfileRegistry::retrieve($profileName);
  }

  // rmdir
  public function downloadZip(array $attachments, $fullPath = '', $keepFile = false):string
  {
    // zip time
    $zipName = uniqid('archive-', true).'.zip';
    $zipPath = empty($fullPath)? sys_get_temp_dir() : $fullPath;
    $zipPath = $zipPath.DS.$zipName;
    $zip = new Filesystem(new ZipArchiveAdapter($zipPath));

    // copy and add to zip
    foreach($attachments as $attachment)
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
    if($keepFile === false) register_shutdown_function(function() use($zipPath) { unlink($zipPath); });

    return $zipPath;
  }

  public function download(Attachment $attachment, $dir = '', $keepName = false, $keepFile = false): string
  {
    // profile
    $src = ProfileRegistry::retrieve($attachment->profile);

    if(!$src->has($attachment->path)) throw new NotFoundException('File not found');

    // resolve dest ...
    $dest = $keepName? strtolower( time() . '_' . preg_replace('/[^a-z0-9_.]/i', '', $attachment->name) ): uniqid('file-download-', true);
    if($keepName && $attachment->type == 'embed') $dest .= '.html';

    if(!empty($dir))
    {
      $this->profile->createDir($dir);
      $dest = $dir.DS.$dest;
    }

    // copy file ...
    $this->profile->put($dest, $attachment->type == 'embed'? $attachment->embed: $src->read($attachment->path));
    $path = $this->profile->getFullPath($dest);

    // delete file when over
    if($keepFile === false) register_shutdown_function(function() use($path) { unlink($path); });

    return $path;
  }
}
