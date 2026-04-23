<?php
declare(strict_types=1);

namespace Trois\Attachment\Filesystem\Protect;

use Aws\S3\S3Client;
use Cake\Cache\Cache;

class AwsS3PreSignedUrlsProtection extends BaseProtection
{
  protected array $_defaultConfig = [
    'bucket' => null,
    'region' => null,
    'version' => 'latest',
    'endpoint' => null,
    'use_path_style_endpoint' => true,
    'credentials' => null,
    // TTL of the signed URL (seconds or strtotime-relative string).
    'ttl' => '+20 minutes',
    // Optional CakePHP cache config used to memoize signed URLs. The cache TTL
    // MUST be lower than the signed URL TTL so no expired URL is served.
    'cacheConfig' => null,
  ];

  protected ?S3Client $_client = null;

  public function getClient(): S3Client
  {
    if ($this->_client !== null) return $this->_client;

    $config = [
      'version' => $this->getConfig('version'),
      'region' => $this->getConfig('region'),
      'credentials' => $this->getConfig('credentials'),
    ];
    if ($endpoint = $this->getConfig('endpoint')) {
      $config['endpoint'] = $endpoint;
      $config['use_path_style_endpoint'] = (bool)$this->getConfig('use_path_style_endpoint');
    }

    return $this->_client = new S3Client($config);
  }

  public function getSignedUrl(string $path, string $baseUrl): string
  {
    $cacheConfig = $this->getConfig('cacheConfig');
    if ($cacheConfig) {
      $key = 'att_s3_' . sha1($this->getConfig('bucket') . '|' . $path);
      $cached = Cache::read($key, $cacheConfig);
      if (is_string($cached) && $cached !== '') return $cached;
    }

    $cmd = $this->getClient()->getCommand('GetObject', [
      'Bucket' => $this->getConfig('bucket'),
      'Key' => $path,
    ]);
    $request = $this->getClient()->createPresignedRequest($cmd, $this->ttl());
    $url = (string)$request->getUri();

    if ($cacheConfig) {
      Cache::write($key, $url, $cacheConfig);
    }

    return $url;
  }

  protected function ttl(): string
  {
    $ttl = $this->getConfig('ttl');
    if (is_int($ttl)) return '+' . $ttl . ' seconds';
    return (string)$ttl;
  }
}
