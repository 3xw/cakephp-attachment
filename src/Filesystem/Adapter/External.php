<?php

namespace Attachment\Filesystem\Adapter;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\Config;
use Cake\Http\Client;

class External extends AbstractAdapter
{
  use StreamedTrait;
  use StreamedCopyTrait;

  private function _makeNicePathUrl($path)
  {
    return str_replace(['http:/','https:/'], ['http://','https://'], $path);
  }

  /**
  * Check whether a file is present.
  *
  * @param string $path
  *
  * @return bool
  */
  public function has($path)
  {
    $path = $this->_makeNicePathUrl($path);
    $array = get_headers($path);
    $string = $array[0];
    return (strpos($string,"200"))? true : false;
  }

  /**
  * @inheritdoc
  */
  public function write($path, $contents, Config $config)
  {
    $type = 'file';
    $result = compact('contents', 'type', 'path');

    if ($visibility = $config->get('visibility')) $result['visibility'] = $visibility;

    return $result;
  }

  /**
  * @inheritdoc
  */
  public function update($path, $contents, Config $config)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function read($path)
  {
    $path = $this->_makeNicePathUrl($path);
    $http = new Client();
    $response = $http->get($path);
    if($response->getStatusCode()){
      $contents = $response->getBody();
      return compact('contents', 'path');
    }
    return false;
  }

  /**
  * @inheritdoc
  */
  public function rename($path, $newpath)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function delete($path)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function listContents($directory = '', $recursive = false)
  {
    return [];
  }

  /**
  * @inheritdoc
  */
  public function getMetadata($path)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function getSize($path)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function getMimetype($path)
  {
    $path = $this->_makeNicePathUrl($path);
    $array = get_headers($path);
    if(!empty($array[1])){
      return ['mimetype' => str_replace('Content-Type: ','',$array[1])];
    }
    return false;
  }

  /**
  * @inheritdoc
  */
  public function getTimestamp($path)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function getVisibility($path)
  {
    return false;
  }

  /**
  * @inheritdoc
  */
  public function setVisibility($path, $visibility)
  {
    return compact('visibility');
  }

  /**
  * @inheritdoc
  */
  public function createDir($dirname, Config $config)
  {
    return ['path' => $dirname, 'type' => 'dir'];
  }

  /**
  * @inheritdoc
  */
  public function deleteDir($dirname)
  {
    return false;
  }
}
