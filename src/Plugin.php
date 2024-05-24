<?php
declare(strict_types=1);

namespace Trois\Attachment;

use Cake\Core\BasePlugin;
use Cake\ORM\TableRegistry;
use Cake\Routing\RouteBuilder;
use Cake\Console\CommandCollection;
use Cake\Core\PluginApplicationInterface;
use Cake\Core\Configure;

class Plugin extends BasePlugin
{
  public function bootstrap(PluginApplicationInterface $app): void
  {
    parent::bootstrap($app);
    
    if(!empty(Configure::read('Trois/Attachment.browse.user_filter_tag_types'))){
      $usersTable = TableRegistry::getTableLocator()->get('Users');
      $usersTable->addBehavior('Trois/Attachment.UserATags');
    }

    

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
