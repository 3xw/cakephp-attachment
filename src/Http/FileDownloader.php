<?php
namespace Trois\Attachment\Http;

use const UPLOAD_ERR_OK;

use Zend\Diactoros\StreamFactory;
use Zend\Diactoros\UploadedFileFactory;

class FileDownloader
{
  static public $streamFactory = null;

  static public $uploadFileFactory = null;

  static public function getStreamFactory(): StreamFactory
  {
    if(!self::$streamFactory) self::$streamFactory = new StreamFactory();
    return self::$streamFactory;
  }

  static public function getUploadedFileFactory(): UploadedFileFactory
  {
    if(!self::$uploadFileFactory) self::$uploadFileFactory = new UploadedFileFactory();
    return self::$uploadFileFactory;
  }

  static public function urlToFormArray(string $url, string $profile = 'default'): array
  {
    // check url
    if(empty($url) && substr($url, 0, 4) == 'http' ) return self::createFormArray($profile);

    // get name
    $parts = explode('/',$url);
    $name = array_pop($parts);

    // get headers
    $headers = get_headers($url,1);

    // check status
    if(substr($headers[0], 9, 3) != 200) return self::createFormArray($profile, $name);

    // set content type
    $clientMediaType = null;
    if(!empty($headers['Content-Type'])) $clientMediaType = $headers['Content-Type'];
    if(!empty($headers['content-type'])) $clientMediaType = $headers['content-type'];

    // download & return array
    return self::createFormArray($profile, $name, $clientMediaType, file_get_contents($url));
  }

  static public function createFormArray(string $profile = 'default', string $name = '', string $clientMediaType = null, string $content = ''): array
  {
    // create stream
    $stream = self::getStreamFactory()->createStream($content);

    // build status
    $status = $stream->getSize()? UPLOAD_ERR_OK: UPLOAD_ERR_NO_FILE;

    // create uploadFile
    $uploadFile = self::getUploadedFileFactory()->createUploadedFile($stream, null, $status, $name, $clientMediaType);

    // return array
    return ['name' => $name, 'profile' => $profile, 'path' => $uploadFile];
  }
}
