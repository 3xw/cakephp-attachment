<?php
declare(strict_types=1);

namespace Trois\Attachment\Test\TestCase\Model\Table;

use Trois\Attachment\Model\Table\AtagTypesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Model\Table\AtagTypesTable Test Case
 */
class AtagTypesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Attachment\Model\Table\AtagTypesTable
     */
    public $AtagTypes;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Attachment.AtagTypes',
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
        $config = TableRegistry::getTableLocator()->exists('AtagTypes') ? [] : ['className' => AtagTypesTable::class];
        $this->AtagTypes = TableRegistry::getTableLocator()->get('AtagTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AtagTypes);

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
}
