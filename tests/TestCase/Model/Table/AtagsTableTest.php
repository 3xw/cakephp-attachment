<?php
declare(strict_types=1);

namespace Trois\Attachment\Test\TestCase\Model\Table;

use Trois\Attachment\Model\Table\AtagsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Model\Table\AtagsTable Test Case
 */
class AtagsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Attachment\Model\Table\AtagsTable
     */
    public $Atags;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Attachment.Atags',
        'plugin.Attachment.AtagTypes',
        'plugin.Attachment.Users',
        'plugin.Attachment.Attachments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Atags') ? [] : ['className' => AtagsTable::class];
        $this->Atags = TableRegistry::getTableLocator()->get('Atags', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Atags);

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
