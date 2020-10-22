<?php
namespace Trois\Attachment\Test\TestCase\Shell;

use Trois\Attachment\Shell\CreateMissingTranslationsShell;
use Cake\TestSuite\TestCase;

/**
 * Attachment\Shell\CreateMissingTranslationsShell Test Case
 */
class CreateMissingTranslationsShellTest extends TestCase
{

    /**
     * ConsoleIo mock
     *
     * @var \Cake\Console\ConsoleIo|\PHPUnit_Framework_MockObject_MockObject
     */
    public $io;

    /**
     * Test subject
     *
     * @var \Attachment\Shell\CreateMissingTranslationsShell
     */
    public $CreateMissingTranslations;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $this->CreateMissingTranslations = new CreateMissingTranslationsShell($this->io);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->CreateMissingTranslations);

        parent::tearDown();
    }

    /**
     * Test getOptionParser method
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
