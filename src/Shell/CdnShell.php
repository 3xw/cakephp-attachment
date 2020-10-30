<?php
namespace Trois\Attachment\Shell;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Console\Shell;
use Cake\Console\ConsoleOptionParser;

class CdnShell extends Shell
{
  public $tasks = ['Trois/Attachment.Clear'];

  public function getOptionParser():ConsoleOptionParser
  {
    $parser = parent::getOptionParser()
    ->addSubcommand('clear', [
      'help' => 'Clear CDN',
      'parser' => $this->Clear->getOptionParser(),
    ]);

    return $parser;
  }

  public function main()
  {
    $this->out($this->OptionParser->help());
  }
}
