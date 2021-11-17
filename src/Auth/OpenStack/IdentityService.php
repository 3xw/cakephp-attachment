<?php
declare(strict_types=1);

namespace Trois\Attachment\Auth\OpenStack;

use GuzzleHttp\ClientInterface;
use OpenStack\Identity\v3\Service;

class IdentityService extends Service
{
  public static function factory(ClientInterface $client): \OpenStack\Identity\v3\Service
  {
    return new static($client, new Api());
  }

  public function generateToken(array $options): \OpenStack\Identity\v3\Models\Token
  {
    return $this->model(Token::class)->create($options);
  }
}
