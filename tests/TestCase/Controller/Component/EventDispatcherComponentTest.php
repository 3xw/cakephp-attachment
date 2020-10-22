<?php
namespace Trois\Attachment\Test\TestCase\Controller\Component;

use Trois\Attachment\Controller\Component\EventDispatcherComponent;
use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Controller\Component\EventDispatcherComponent Test Case
 */
class EventDispatcherComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Attachment\Controller\Component\EventDispatcherComponent
     */
    public $EventDispatcher;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->EventDispatcher = new EventDispatcherComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->EventDispatcher);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
