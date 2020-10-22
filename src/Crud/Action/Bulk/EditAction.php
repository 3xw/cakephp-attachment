<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action\Bulk;

use Cake\Controller\Controller;
use Cake\ORM\Query;
use Cake\I18n\Time;

class EditAction extends BaseJsonRestAction
{
  protected function _bulk(?Query $query = null): bool
  {
    // retrieve
    $associated = $this->getConfig('relatedModels')?? [];
    $query->contain($associated);
    $indexedList = $query->toArray();

    // patch
    $patched = [];
    foreach($indexedList as $pk => $entity)
    {
      if($this->subject->data[$pk]['date']){
        $date = new Time($this->subject->data[$pk]['date']);
        $this->subject->data[$pk]['date'] = $date->format('Y-m-d H:i:s');
      }
      $patched[] = $this->_table()->patchEntity(
        $entity,
        $this->subject->data[$pk],
        ['associated' => $associated ]
      );
    }
    // save
    return (bool) $this->_table()->saveMany($patched, ['associated' => $associated ]);
  }
}
