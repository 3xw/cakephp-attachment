<?php
declare(strict_types=1);

namespace Trois\Attachment\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Trois\Attachment\Filesystem\ProfileRegistry;

/**
 * Copy every file from a source profile to a destination profile.
 *
 * Stream-based: no full payload held in memory. Skips files that already
 * exist on the destination unless --overwrite is passed.
 *
 * Usage:
 *   bin/cake at_migrate_storage <from> <to> [--dry-run] [--overwrite]
 */
class MigrateStorageCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->setDescription('Migrate files from one attachment profile to another.')
            ->addArgument('from', ['help' => 'Source profile name', 'required' => true])
            ->addArgument('to', ['help' => 'Destination profile name', 'required' => true])
            ->addOption('dry-run', ['boolean' => true, 'help' => 'List what would be copied without writing'])
            ->addOption('overwrite', ['boolean' => true, 'help' => 'Overwrite files that already exist on the destination']);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $from = (string)$args->getArgument('from');
        $to = (string)$args->getArgument('to');
        $dryRun = (bool)$args->getOption('dry-run');
        $overwrite = (bool)$args->getOption('overwrite');

        $registry = new ProfileRegistry();
        $source = $registry->retrieve($from);
        $dest = $registry->retrieve($to);

        $io->out(sprintf('Migrating "%s" → "%s"%s', $from, $to, $dryRun ? ' (dry-run)' : ''));

        $copied = $skipped = $failed = 0;
        foreach ($source->listContents('', true) as $item) {
            if (($item['type'] ?? null) !== 'file') continue;
            $path = $item['path'];

            if (!$overwrite && $dest->has($path)) {
                $io->verbose('skip ' . $path);
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $io->out('copy ' . $path);
                $copied++;
                continue;
            }

            try {
                $stream = $source->readStream($path);
                if (!is_resource($stream)) throw new \RuntimeException('readStream returned non-resource');
                if ($dest->has($path)) $dest->delete($path, true);
                $dest->writeStream($path, $stream);
                if (is_resource($stream)) fclose($stream);
                $io->out('copy ' . $path);
                $copied++;
            } catch (\Throwable $e) {
                $io->err(sprintf('fail %s — %s', $path, $e->getMessage()));
                $failed++;
            }
        }

        $io->out(sprintf('done: %d copied, %d skipped, %d failed', $copied, $skipped, $failed));
        return $failed === 0 ? static::CODE_SUCCESS : static::CODE_ERROR;
    }
}
