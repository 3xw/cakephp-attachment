<?php
declare(strict_types=1);

namespace Trois\Attachment\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Trois\Attachment\Filesystem\ProfileRegistry;

/**
 * Profile command.
 */
class ProfileCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
      $profileRegistry = new ProfileRegistry;

      if(
        (!$profileName = $args->getArgumentAt(0)) ||
        (!$localPath = $args->getArgumentAt(1)) ||
        (!$path = $args->getArgumentAt(2)) ||
        (!$mime = $args->getArgumentAt(3))
      )
      throw new \Exception("You must provide profile localPath path mime", 1);

      $profile = $profileRegistry->retrieve($profileName);
      $profile->store($localPath, $path, false, 'public', $mime);
      debug($profile->getMetadata($path));
      $profile->delete($path);
    }
}
