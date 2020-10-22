<?php
declare(strict_types=1);

namespace Trois\Attachment\Filesystem;

use Cake\Filesystem\File;
use Zend\Diactoros\UploadedFile as ZUploadedFile;

class UploadedFile
{
  public $zFile;

  protected $_path;

  public function __construct(ZUploadedFile $zFile)
  {
    $this->zFile = $zFile;
    $this->_metadata = $this->zFile->getStream()->getMetadata();

    // move file
    $this->_moveFile();
  }

  protected function _moveFile()
  {
    $path = tempnam(sys_get_temp_dir(), 'uploaded_file');
    $this->zFile->moveTo($path);

    // remove file after shut down!!
    register_shutdown_function(function() use($path) { unlink($path); });

    $this->_path = $path;
  }

  public function getPath()
  {
    return $this->_path;
  }

  public function getMetadata()
  {
    return @exif_read_data($this->_path);
  }

  public function getSize()
  {
    return $this->zFile->getSize();
  }

  public function getError()
  {
    return $this->zFile->getError();
  }

  public function getClientFilename()
  {
    return $this->zFile->getClientFilename();
  }

  public function getClientMediaType()
  {
    return $this->zFile->getClientMediaType();
  }
}
