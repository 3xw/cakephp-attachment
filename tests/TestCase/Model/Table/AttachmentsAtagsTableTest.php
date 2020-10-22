<?php
namespace Trois\Attachment\Test\TestCase\Model\Table;

use Trois\Attachment\Model\Table\AttachmentsAtagsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Model\Table\AttachmentsAtagsTable Test Case
 */
class AttachmentsAtagsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Attachment\Model\Table\AttachmentsAtagsTable
     */
    public $AttachmentsAtags;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.attachment.attachments_atags',
        'plugin.attachment.attachments',
        'plugin.attachment.atags'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('AttachmentsAtags') ? [] : ['className' => 'Trois\Attachment\Model\Table\AttachmentsAtagsTable'];
        $this->AttachmentsAtags = TableRegistry::get('AttachmentsAtags', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AttachmentsAtags);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
