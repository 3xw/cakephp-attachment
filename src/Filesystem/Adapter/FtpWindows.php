<?php
namespace Trois\Attachment\Filesystem\Adapter;

use League\Flysystem\Adapter\Ftp;

class FtpWindows extends Ftp
{
  public function setVisibility($path, $visibility)
  {
    return compact('path', 'visibility');
  }
}
