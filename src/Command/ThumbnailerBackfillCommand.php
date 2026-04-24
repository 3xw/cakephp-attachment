<?php
declare(strict_types=1);

namespace Trois\Attachment\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

use function Cake\Core\env;

/**
 * Pre-generate the default thumbnail dims for every existing attachment in
 * a given profile. The Rust `thumbnailer` sidecar fans out one encode task
 * per dim via its internal tokio semaphore, so we throttle client-side by
 * firing a small batch in parallel (curl_multi) then waiting before the
 * next batch.
 *
 * Usage:
 *   bin/cake at_thumbnailer_backfill \
 *     [--profile=default] [--batch=4] [--sleep=4] [--limit=0] \
 *     [--type=image] [--dry-run]
 */
class ThumbnailerBackfillCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->setDescription('Pre-generate default thumbnails for existing attachments via the Rust sidecar.')
            ->addOption('profile', ['default' => 'default', 'help' => 'Attachment profile to backfill'])
            ->addOption('batch',   ['default' => '4',        'help' => 'Parallel /warm calls per wave'])
            ->addOption('sleep',   ['default' => '4',        'help' => 'Seconds to wait between waves'])
            ->addOption('limit',   ['default' => '0',        'help' => '0 = no limit'])
            ->addOption('type',    ['help' => 'Filter by attachment type (image|video|application)'])
            ->addOption('dry-run', ['boolean' => true,       'help' => 'Print plan without firing any HTTP calls']);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $profile   = (string)$args->getOption('profile');
        $batchSize = max(1, (int)$args->getOption('batch'));
        $sleepSec  = max(0, (int)$args->getOption('sleep'));
        $limit     = max(0, (int)$args->getOption('limit'));
        $typeFlt   = (string)($args->getOption('type') ?? '');
        $dryRun    = (bool)$args->getOption('dry-run');

        $dims = Configure::read('Trois/Attachment.thumbnailer.default_dims', []);
        if (empty($dims)) {
            $io->error('No default dims configured: set Trois/Attachment.thumbnailer.default_dims');
            return Command::CODE_ERROR;
        }
        $warmUrl = rtrim((string)env(
            'THUMBNAILER_INTERNAL_URL',
            (string)Configure::read('Trois/Attachment.thumbnailer.internal_url', 'http://thumbnailer:8081')
        ), '/') . '/warm';

        $Attachments = $this->fetchTable('Trois/Attachment.Attachments');
        $query = $Attachments->find()
            ->where(['Attachments.profile' => $profile])
            ->where(['Attachments.path IS NOT' => null])
            ->select(['id', 'path', 'profile', 'type', 'subtype', 'size', 'name'])
            ->orderBy(['created' => 'DESC']);
        if ($typeFlt !== '') $query->where(['Attachments.type' => $typeFlt]);
        if ($limit > 0)      $query->limit($limit);

        // count() ignores the query's LIMIT — for progress reporting we want
        // the effective number of rows we're going to iterate over.
        $total = $query->count();
        if ($limit > 0 && $limit < $total) {
            $total = $limit;
        }
        $io->out(sprintf(
            '<info>Backfill</info> profile=%s total=%d dims=%s batch=%d sleep=%ds%s',
            $profile, $total, implode(',', $dims), $batchSize, $sleepSec, $dryRun ? ' [dry-run]' : ''
        ));
        $io->out(sprintf('Sidecar: %s', $warmUrl));
        if ($total === 0) {
            $io->warning('No attachments matched the filter.');
            return Command::CODE_SUCCESS;
        }

        $done = 0;
        $failed = 0;
        $start = microtime(true);
        $batch = [];

        foreach ($query->all() as $a) {
            $batch[] = $a;
            if (count($batch) < $batchSize) continue;

            [$ok, $ko] = $this->fireBatch($warmUrl, $batch, $dims, $profile, $dryRun);
            $done += $ok;
            $failed += $ko;
            $batch = [];

            $this->progress($io, $done, $failed, $total, $start);
            if ($sleepSec > 0 && $done < $total) sleep($sleepSec);
        }
        if (!empty($batch)) {
            [$ok, $ko] = $this->fireBatch($warmUrl, $batch, $dims, $profile, $dryRun);
            $done += $ok;
            $failed += $ko;
            $this->progress($io, $done, $failed, $total, $start);
        }

        $io->out('');
        $io->success(sprintf(
            'Done: %d queued (%d failed) in %.1fs — thumbs keep encoding in the sidecar for a few more minutes.',
            $done, $failed, microtime(true) - $start
        ));
        return Command::CODE_SUCCESS;
    }

    /**
     * Fire all rows of $batch in parallel via curl_multi. /warm returns 202
     * as soon as the sidecar spawns its tokio tasks (usually <100ms), so the
     * wait here is bounded by network RTT + the size of $batch.
     *
     * @return array{0:int,1:int} [ok, failed]
     */
    private function fireBatch(string $url, array $batch, array $dims, string $profile, bool $dryRun): array
    {
        if ($dryRun) {
            return [count($batch), 0];
        }
        $mh = curl_multi_init();
        $handles = [];
        foreach ($batch as $a) {
            $payload = json_encode([
                'path' => (string)$a->path,
                'dims' => array_values($dims),
                'profile' => $profile,
            ]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT_MS => 500,
                CURLOPT_TIMEOUT_MS => 3000,
                CURLOPT_NOSIGNAL => true,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running > 0) curl_multi_select($mh, 1.0);
        } while ($running > 0 && $status === CURLM_OK);

        $ok = 0;
        $ko = 0;
        foreach ($handles as $ch) {
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code >= 200 && $code < 300) $ok++; else $ko++;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return [$ok, $ko];
    }

    private function progress(ConsoleIo $io, int $done, int $failed, int $total, float $start): void
    {
        $pct = $total > 0 ? ($done / $total) * 100 : 100;
        $elapsed = max(0.001, microtime(true) - $start);
        $rate = $done / $elapsed;
        $eta = $rate > 0 ? ($total - $done) / $rate : 0;
        $io->overwrite(sprintf(
            '  %5d / %d (%.1f%%)  fails=%d  rate=%.1f/s  ETA=%.0fs',
            $done, $total, $pct, $failed, $rate, $eta
        ));
    }
}
