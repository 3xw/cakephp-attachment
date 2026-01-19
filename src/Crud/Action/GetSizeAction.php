<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action;

use Cake\Http\Exception\NotFoundException;
use Cake\Controller\Controller;
use Cake\ORM\Query;
use Crud\Action\Bulk\BaseAction;
use Crud\Event\Subject;
use Cake\Http\Response;
use Cake\Routing\Router;
use Crud\Traits\FindMethodTrait;
use Crud\Traits\SerializeTrait;
use Crud\Traits\ViewTrait;
use Crud\Traits\ViewVarTrait;

class GetSizeAction extends \Crud\Action\BaseAction
{
    use FindMethodTrait;
    use SerializeTrait;
    use ViewTrait;
    use ViewVarTrait;

    /**
     * Default settings
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'enabled' => true,
        'scope' => 'table',
        'findMethod' => 'all',
        'view' => null,
        'viewVar' => null,
        'serialize' => [],
        'api' => [
            'success' => [
                'code' => 200
            ],
            'error' => [
                'code' => 400
            ]
        ]
    ];

    /**
    * Generic handler for all HTTP verbs
    *
    * @return void
    */
    protected function _handle(): ?Response
    {
      $total = 0;

      [$finder, $options] = $this->_extractFinder();
      $items = $this->_table()->find('search', ['search' => $this->_request()->getQuery()])->toArray();

      foreach($items as $item) $total += $item->size;

      $subject = $this->_subject(['success' => true]);

      $subject->set(['entities' => [['uuid' => $this->_request()->getQuery('uuid'), 'size' => $total]]]);

      $this->_trigger('beforeRender', $subject);

      return null;
    }
}
