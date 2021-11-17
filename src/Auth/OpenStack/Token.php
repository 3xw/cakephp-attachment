<?php
declare(strict_types=1);

namespace Trois\Attachment\Auth\OpenStack;

use OpenStack\Common\Transport\Utils;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Identity\v3\Models\Token as BaseToken;

class Token extends BaseToken
{
  public function create(array $data): Creatable
  {
    if (isset($data['user'])) {
      $data['methods'] = ['password'];
      if (!isset($data['user']['id']) && empty($data['user']['domain'])) {
        throw new \InvalidArgumentException('When authenticating with a username, you must also provide either the domain name or domain ID to '.'which the user belongs to. Alternatively, if you provide a user ID instead, you do not need to '.'provide domain information.');
      }
    } elseif (isset($data['tokenId'])) {
      $data['methods'] = ['token'];
    }
    if (isset($data['application_credential'])) {
      $data['methods'] = [
        'type' => 'application_credential'
      ];
      if (empty($data['application_credential']['id']) || empty($data['application_credential']['secret'])) {
        throw new \InvalidArgumentException('When authenticating with a application_credential, you must also provide id and secret.');
      }
    }else {
      throw new \InvalidArgumentException('Either a user a token or an app creditieal must be provided.');
    }

    $response = $this->execute($this->api->postTokens(), $data);
    $token    = $this->populateFromResponse($response);

    // Cache response as an array to export if needed.
    // Added key `id` which is auth token from HTTP header X-Subject-Token
    $this->cachedToken       = Utils::flattenJson(Utils::jsonDecode($response), $this->resourceKey);
    $this->cachedToken['id'] = $token->id;

    return $token;
  }
}
