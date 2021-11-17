<?php
declare(strict_types=1);

namespace Trois\Attachment\Auth\OpenStack;

use OpenStack\Identity\v3\Params as BaseParams;

class Params extends BaseParams
{
  public function applicationCredential(): array
  {
    return [
      'type'       => self::OBJECT_TYPE,
      'path'       => 'auth.identity',
      'properties' => [
        'id' => [
          'type'        => self::STRING_TYPE,
          'description' => 'app id',
        ],
        'secret' => [
          'type'        => self::STRING_TYPE,
          'description' => 'The secret of the app',
        ],
        'domain' => $this->domain(),
      ],
    ];
  }
}
