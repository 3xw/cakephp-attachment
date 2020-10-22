<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Listener;

use Crud\Listener\ApiListener;

class JsonApiListener extends ApiListener
{
  public function implementedEvents(): array
  {
    // TEST IF JSON DATA ARE PRESENT THEN REPLACE...
    $dataArray = $this->_controller()->getRequest()->input('json_decode', true);
    if(!empty($dataArray))
    {
      foreach($dataArray as $key => $value) $request = $this->_controller()->getRequest()->withData($key, $value);
      $this->_controller()->setRequest($request);
    }

    // DO AS USU
    return parent::implementedEvents();
  }
}
