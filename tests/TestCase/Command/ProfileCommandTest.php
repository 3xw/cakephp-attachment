<?php
declare(strict_types=1);

namespace Trois\Attachment\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Trois\Attachment\Command\ProfileCommand;

/**
 * Trois\Attachment\Command\ProfileCommand Test Case
 *
 * @uses \Trois\Attachment\Command\ProfileCommand
 */
class ProfileCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }
    /**
     * Test buildOptionParser method
     *
     * @return void
     * @uses \Trois\Attachment\Command\ProfileCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \Trois\Attachment\Command\ProfileCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
