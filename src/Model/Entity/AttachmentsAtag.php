<?php
namespace Attachment\Model\Entity;

use Cake\ORM\Entity;

/**
 * AttachmentsAtag Entity
 *
 * @property int $attachment_id
 * @property int $atag_id
 *
 * @property \Attachment\Model\Entity\Attachment $attachment
 * @property \Attachment\Model\Entity\Atag $atag
 */
class AttachmentsAtag extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity([]) or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'attachment_id' => false,
        'atag_id' => false
    ];
}
