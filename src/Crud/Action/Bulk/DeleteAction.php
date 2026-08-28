<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action\Bulk;

use Cake\Controller\Controller;
use Cake\Database\Query;
use Crud\Action\Bulk\BaseAction;

class DeleteAction extends BaseAction
{
  public function __construct(Controller $Controller, array $config = [])
  {
    // Crud choisit le type de requete via cette cle et prend TYPE_UPDATE par
    // defaut, ce qui produisait une UpdateQuery - sans toArray(). Cette action
    // a besoin de lire les entites pour les supprimer une a une, afin que les
    // fichiers associes soient retires du stockage au passage.
    $this->_defaultConfig['queryType'] = Query::TYPE_SELECT;

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

  // Le parent (Crud\Action\Bulk\BaseAction) type ce parametre avec
  // Cake\Database\Query et le declare obligatoire. Sous CakePHP 5 la
  // classe Cake\ORM\Query n'en est plus la meme : PHP refusait de charger
  // la classe, et toute suppression groupee finissait en erreur fatale.
  // L'objet recu reste une SelectQuery, donc toArray() fonctionne.
  protected function _bulk(Query $query): bool
  {
    $list = $query->toArray();

    foreach($list as $entity) if(!$bool = $this->_table()->delete($entity)) return false;

    return true;
  }
}
