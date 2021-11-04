<?php
declare(strict_types=1);

namespace Trois\Attachment;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Cake\Console\CommandCollection;
use Cake\Core\PluginApplicationInterface;

class Plugin extends BasePlugin
{
  public function bootstrap(PluginApplicationInterface $app): void
  {
    parent::bootstrap($app);
  }

  public function console(CommandCollection $commands): CommandCollection
  {
    return $commands
    ->add('at_profile', \Trois\Attachment\Command\ProfileCommand::class)
    ->add('at_get_image_sizes', \Trois\Attachment\Shell\GetImageSizesShell::class)
    ->add('at_creat_missing_translations', \Trois\Attachment\Shell\CreateMissingTranslationsShell::class);
  }


  public function routes(RouteBuilder $routes): void
  {
    parent::routes($routes);
  }
}
