<?php
declare(strict_types=1);

namespace Trois\Attachment\Model\Entity;

use Cake\ORM\Entity;

/**
* Aarchive Entity
*
* @property int $id
* @property string $state
* @property \Cake\I18n\FrozenTime $created
* @property \Cake\I18n\FrozenTime $modified
* @property string $aids
* @property string|null $user_id
* @property string|null $attachment_id
*
* @property \Attachment\Model\Entity\User $user
* @property \Attachment\Model\Entity\Attachment $attachment
*/
class Aarchive extends Entity
{

  /**
  * Fields that can be mass assigned using newEntity() or patchEntity().
  *
  * Note that when '*' is set to true, this allows all unspecified fields to
  * be mass assigned. For security purposes, it is advised to set '*' to false
  * (or remove it), and explicitly make individual fields accessible as needed.
  *
  * @var array
  */
  protected array $_accessible = [
    '*' => true,
    'id' => false,
  ];
}
