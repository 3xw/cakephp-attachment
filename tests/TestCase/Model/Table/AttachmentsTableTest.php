<?php
declare(strict_types=1);

namespace Attachment\Test\TestCase\Model\Table;

use Attachment\Model\Table\AttachmentsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Model\Table\AttachmentsTable Test Case
 */
class AttachmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Attachment\Model\Table\AttachmentsTable
     */
    public $Attachments;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Attachment.Attachments',
        'plugin.Attachment.Users',
        'plugin.Attachment.Atags',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Attachments') ? [] : ['className' => AttachmentsTable::class];
        $this->Attachments = TableRegistry::getTableLocator()->get('Attachments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Attachments);

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
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
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
