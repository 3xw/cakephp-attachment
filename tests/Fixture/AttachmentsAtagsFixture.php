<?php
namespace Trois\Attachment\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AttachmentsAtagsFixture
 *
 */
class AttachmentsAtagsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'attachment_id' => ['type' => 'biginteger', 'length' => 20, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'atag_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_indexes' => [
            'fk_attachments_has_atags_atags1_idx' => ['type' => 'index', 'columns' => ['atag_id'], 'length' => []],
            'fk_attachments_has_atags_attachments1_idx' => ['type' => 'index', 'columns' => ['attachment_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['attachment_id', 'atag_id'], 'length' => []],
            'fk_attachments_has_atags_atags1' => ['type' => 'foreign', 'columns' => ['atag_id'], 'references' => ['atags', 'id'], 'update' => 'noAction', 'delete' => 'cascade', 'length' => []],
            'fk_attachments_has_atags_attachments1' => ['type' => 'foreign', 'columns' => ['attachment_id'], 'references' => ['attachments', 'id'], 'update' => 'noAction', 'delete' => 'cascade', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'attachment_id' => 1,
            'atag_id' => 1
        ],
    ];
}
