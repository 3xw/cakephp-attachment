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

    // WGRC-803 — scope filtering moved out of UserATagsBehavior (which only
    // ever did `$query->contain(['Atags'])` and never actually filtered) into
    // `ScopedBrowsingBehavior`, attached directly on AttachmentsTable. The
    // host app wires `Users.belongsToMany('Atags')` so the lookup works.
  }

  public function console(CommandCollection $commands): CommandCollection
  {
    return $commands
      ->add('at_profile', \Trois\Attachment\Command\ProfileCommand::class)
      ->add('at_get_image_sizes', \Trois\Attachment\Command\GetImageSizesCommand::class)
      ->add('at_create_missing_translations', \Trois\Attachment\Command\CreateMissingTranslationsCommand::class)
      ->add('at_migrate_storage', \Trois\Attachment\Command\MigrateStorageCommand::class)
      ->add('at_thumbnailer_backfill', \Trois\Attachment\Command\ThumbnailerBackfillCommand::class);
  }


  public function routes(RouteBuilder $routes): void
  {
    parent::routes($routes);
  }
}
