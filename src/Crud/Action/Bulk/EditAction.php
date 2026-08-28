<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action\Bulk;

use Cake\Controller\Controller;
use Cake\Database\Query;
use Cake\I18n\Time;

class EditAction extends BaseJsonRestAction
{
  // Le parent (Crud\Action\Bulk\BaseAction) type ce parametre avec
  // Cake\Database\Query et le declare obligatoire. Sous CakePHP 5 la
  // classe Cake\ORM\Query n'en est plus la meme : PHP refusait de charger
  // la classe, et toute suppression groupee finissait en erreur fatale.
  // L'objet recu reste une SelectQuery, donc toArray() fonctionne.
  protected function _bulk(Query $query): bool
  {
    // retrieve
    $associated = $this->getConfig('relatedModels')?? [];
    $query->contain($associated);
    $indexedList = $query->toArray();

    // patch
    $patched = [];
    foreach($indexedList as $pk => $entity)
    {
      // if($this->subject->data[$pk]['date']){
      //   $date = new Time($this->subject->data[$pk]['date']);
      //   $this->subject->data[$pk]['date'] = $date->format('Y-m-d H:i:s');
      // }
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
