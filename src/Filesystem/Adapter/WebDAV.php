<?php
namespace Trois\Attachment\Filesystem\Adapter;

use League\Flysystem\Config;
use League\Flysystem\Util;
use League\Flysystem\WebDAV\WebDAVAdapter;

class WebDAV extends WebDAVAdapter
{
  /**
   * {@inheritdoc}
   */
  public function writeStream($path, $resource, Config $config)
  {
      $contents = stream_get_contents($resource);
      return $this->write($path, $contents, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function write($path, $contents, Config $config)
  {
      if (!$this->createDir(Util::dirname($path), $config)) {
          return false;
      }

      $location = $this->applyPathPrefix($this->encodePath($path));
      $response = $this->client->request('PUT', $location, $contents);

      if ($response['statusCode'] >= 400) {
          return false;
      }

      $result = compact('path', 'contents');

      return $result;
  }
}
