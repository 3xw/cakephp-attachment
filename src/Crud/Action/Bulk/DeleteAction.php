<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action\Bulk;

use Cake\Controller\Controller;
use Cake\ORM\Query;
use Crud\Action\Bulk\BaseAction;

class DeleteAction extends BaseAction
{
  public function __construct(Controller $Controller, array $config = [])
  {
    $this->_defaultConfig['messages'] = [
      'success' => [
        'text' => 'Delete completed successfully',
      ],
      'error' => [
        'text' => 'Could not complete deletion',
      ],
    ];

    parent::__construct($Controller, $config);
  }

  protected function _bulk(?Query $query = null): bool
  {
    $list = $query->toArray();

    foreach($list as $entity) if(!$bool = $this->_table()->delete($entity)) return false;

    return true;
  }
}
