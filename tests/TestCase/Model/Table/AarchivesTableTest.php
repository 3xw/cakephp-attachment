<?php
declare(strict_types=1);

namespace Attachment\Test\TestCase\Model\Table;

use Attachment\Model\Table\AarchivesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Model\Table\AarchivesTable Test Case
 */
class AarchivesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Attachment\Model\Table\AarchivesTable
     */
    public $Aarchives;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Attachment.Aarchives',
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
        $config = TableRegistry::getTableLocator()->exists('Aarchives') ? [] : ['className' => AarchivesTable::class];
        $this->Aarchives = TableRegistry::getTableLocator()->get('Aarchives', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Aarchives);

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
