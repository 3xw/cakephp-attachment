<?php
declare(strict_types=1);

namespace Attachment\Model\Entity;

use Cake\ORM\Entity;

/**
 * Atag Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $atag_type_id
 * @property string|null $user_id
 *
 * @property \Attachment\Model\Entity\AtagType $atag_type
 * @property \Attachment\Model\Entity\User $user
 * @property \Attachment\Model\Entity\Attachment[] $attachments
 */
class Atag extends Entity
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
     protected $_accessible = [
       '*' => true,         
      'id' => false,
            ];
}
