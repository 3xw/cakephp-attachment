<?php
namespace Attachment\Filesystem\Protect;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;

abstract class BaseProtection implements ProtectionInterface
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [];

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  public function verify(ServerRequest $request): bool
  {
    return false;
  }

  public function getSignedUrl(string $path, string $baseUrl): string
  {
    return '';
  }
}
