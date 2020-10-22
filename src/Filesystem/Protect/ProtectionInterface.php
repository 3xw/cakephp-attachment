<?php
namespace Attachment\Filesystem\Protect;

use Cake\Http\ServerRequest;

interface ProtectionInterface
{
  public function verify(ServerRequest $request): bool;

  public function getSignedUrl(string $path, string $baseUrl): string;
}
