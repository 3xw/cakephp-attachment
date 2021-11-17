<?php
declare(strict_types=1);

namespace Trois\Attachment\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use OpenStack\OpenStack;
use OpenStack\Common\Service\Builder;
use OpenStack\Common\Transport\HandlerStack;
use OpenStack\Common\Transport\Utils;

use Trois\Attachment\Auth\OpenStack\IdentityService as Service;

class OpenStackClient extends OpenStack
{
  public function __construct(array $options = [], Builder $builder = null)
  {
    if (!isset($options['identityService'])) {
      $options['identityService'] = $this->getDefaultIdentityService($options);
    }
    parent::__construct($options, $builder);
  }

  private function getDefaultIdentityService(array $options): Service
  {
    if (!isset($options['authUrl'])) throw new \InvalidArgumentException("'authUrl' is a required option");

    $stack = HandlerStack::create();

    if (
      !empty($options['debugLog'])
      && !empty($options['logger'])
      && !empty($options['messageFormatter'])
    ){
      $logMiddleware = GuzzleMiddleware::log($options['logger'], $options['messageFormatter']);
      $stack->push($logMiddleware, 'logger');
    }

    $clientOptions = [
      'base_uri' => Utils::normalizeUrl($options['authUrl']),
      'handler'  => $stack,
    ];

    if (isset($options['requestOptions'])) {
      $clientOptions = array_merge($options['requestOptions'], $clientOptions);
    }

    return Service::factory(new Client($clientOptions));
  }
}
