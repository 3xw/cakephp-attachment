<?php
namespace Trois\Attachment\Filesystem\Protect;

use Aws\CloudFront\UrlSigner;
use Trois\Attachment\Utility\Token;
use Cake\Http\ServerRequest;

class AwsSignedUrlsProtection extends BaseProtection
{
  protected $_urlSigner;

  public function getUrlSigner()
  {
    if(!isset($this->_urlSigner)) $this->_urlSigner = new UrlSigner(
      $this->getConfig('keyPairId'),
      $this->getConfig('privateKey')
    );

    return $this->_urlSigner;
  }

  public function verify(ServerRequest $request): bool
  {
    if (!$token = $request->getParam('?.token')) return false;

    try {
      $payload = Token::decode($token);
    } catch (\Exception $e) {
      return false;
    }

    // retrieve file
    $url = $request->getUri()->getPath();
    $file = substr($url, strrpos($url, '/') + 1 );

    return $payload->file == $file;
  }

  public function getSignedUrl(string $path, string $baseUrl): string
  {
    $url = $baseUrl.$path;
    $url = $this->getUrlSigner()->getSignedUrl(
      $this->getReturnUrl($url),
      $this->getConfig('expires'),
      $this->getPolicy($url)
    );
    return $url;
  }

  public function getReturnUrl(string $url): string
  {
    $fileName = strrpos($url, '/') === false ? $url: substr($url, strrpos($url, '/') + 1 );
    return $url . '?token=' . $this->getReturnToken($fileName);
  }

  public function getReturnToken($fileName)
  {
    if(!$this->getConfig('expires') && !$this->getConfig('DateLessThan')) throw new \InvalidArgumentException('AwsSignedUrlsProtection "expires" and "DateLessThan" params not present');
    $exp = $this->getConfig('expires')? $this->getConfig('expires'): time() + $this->getConfig('DateLessThan');

    return Token::encode([
      'exp' => $exp,
      'file' => $fileName
    ]);
  }

  public function getPolicy($url)
  {
    // min requirement is DateLessThan!
    if (!$this->getConfig('DateLessThan')) return null;

    // create statment
    $statement = (object) ['Resource' => null,'Condition' => []];

    // set conditions
    // resource
    $statement->Resource = $this->getConfig('Resource')? $this->getConfig('Resource'): $url;
    // DateLessThan
    $statement->Condition['DateLessThan'] = ['AWS:EpochTime' => time() + $this->getConfig('DateLessThan') ];
    // DateGreaterThan
    if ($this->getConfig('DateGreaterThan')) $statement->Condition['DateGreaterThan'] = ['AWS:EpochTime' => time() + $this->getConfig('DateGreaterThan') ];
    // IpAddress
    if ($this->getConfig('IpAddress') && !Configure::read('debug')) $statement->Condition['IpAddress'] = ['AWS:SourceIp' => Router::getRequest()->clientIp().'\32' ];

    // JSON policy
    $policy = json_encode((object) [
      'Statement' => [$statement]
    ]);

    // return
    return $policy;
  }
}
