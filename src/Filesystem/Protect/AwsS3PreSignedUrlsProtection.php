<?php
namespace Trois\Attachment\Filesystem\Protect;

use Aws\S3\S3Client;
use Trois\Attachment\Utility\Token;
use Cake\Http\ServerRequest;

class AwsS3PreSignedUrlsProtection extends BaseProtection
{
  protected $_urlSigner;

  protected $_client;

  public function getClient()
  {
    if(!isset($this->_client)) $this->_client = new S3Client([
      'version'     => $this->getConfig('version'),
      'region'      => $this->getConfig('region'),
      'credentials' => $this->getConfig('credentials')
    ]);

    return $this->_client;
  }

  public function getUrlSigner()
  {
    if(!isset($this->_urlSigner)) $this->_urlSigner = new UrlSigner(
      $this->getConfig('keyPairId'),
      $this->getConfig('privateKey')
    );

    return $this->_urlSigner;
  }

  public function getSignedUrl(string $path, string $baseUrl): string
  {
    $cmd = $this->getClient()->getCommand('GetObject', [
      'Bucket' => $this->getConfig('bucket'),
      'Key' => $path
    ]);

    $request = $this->getClient()->createPresignedRequest($cmd, '+20 minutes');
    return (string) $request->getUri();
  }
}
