<?php
declare(strict_types=1);

namespace Trois\Attachment;

use Cake\Core\BasePlugin;
use Cake\Datasource\FactoryLocator;
use Cake\Routing\RouteBuilder;
use Cake\Console\CommandCollection;
use Cake\Core\PluginApplicationInterface;
use Cake\Core\Configure;

class Plugin extends BasePlugin
{
  public function bootstrap(PluginApplicationInterface $app): void
  {
    parent::bootstrap($app);

    if (!empty(Configure::read('Trois/Attachment.browse.user_filter_tag_types'))) {
      $tableLocator = FactoryLocator::get('Table');
      $usersTable = $tableLocator->get('Users');
      $usersTable->addBehavior('Trois/Attachment.UserATags');
    }
  }

  public function console(CommandCollection $commands): CommandCollection
  {
    return $commands
      ->add('at_profile', \Trois\Attachment\Command\ProfileCommand::class)
      ->add('at_get_image_sizes', \Trois\Attachment\Command\GetImageSizesCommand::class)
      ->add('at_create_missing_translations', \Trois\Attachment\Command\CreateMissingTranslationsCommand::class);
  }


  public function routes(RouteBuilder $routes): void
  {
    parent::routes($routes);
  }
}
