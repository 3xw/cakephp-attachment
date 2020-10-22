<?php
use Migrations\AbstractMigration;

class Attachment extends AbstractMigration
{
    public function up()
    {

        $this->table('atags')
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('slug', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addIndex(
                [
                    'name',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'slug',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('attachments', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addColumn('subtype', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addColumn('size', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('md5', 'string', [
                'default' => null,
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('author', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('copyright', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('path', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('embed', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('profile', 'string', [
                'default' => 'default',
                'limit' => 45,
                'null' => false,
            ])
            ->create();

        $this->table('attachments_atags', ['id' => false, 'primary_key' => ['attachment_id', 'atag_id']])
            ->addColumn('attachment_id', 'biginteger', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('atag_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'atag_id',
                ]
            )
            ->addIndex(
                [
                    'attachment_id',
                ]
            )
            ->create();

        $this->table('attachments_atags')
            ->addForeignKey(
                'atag_id',
                'atags',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'CASCADE'
                ]
            )
            ->addForeignKey(
                'attachment_id',
                'attachments',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'CASCADE'
                ]
            )
            ->update();
    }

    public function down()
    {
        $this->table('attachments_atags')
            ->dropForeignKey(
                'atag_id'
            )
            ->dropForeignKey(
                'attachment_id'
            );

        $this->dropTable('atags');
        $this->dropTable('attachments');
        $this->dropTable('attachments_atags');
    }
}
