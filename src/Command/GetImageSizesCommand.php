<?php
declare(strict_types=1);

namespace Trois\Attachment\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Trois\Attachment\Filesystem\FilesystemRegistry;

class GetImageSizesCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Get filesystem for profile
     *
     * @param string $profile Profile name
     * @return mixed
     */
    private function _filesystem(string $profile)
    {
        return FilesystemRegistry::retrieve($profile);
    }

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Extract image dimensions from attachments');

        return $parser;
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        Configure::write('Trois/Attachment.profiles.shell_script', [
            'adapter' => 'League\Flysystem\Adapter\Local',
            'client' => new \League\Flysystem\Adapter\Local(WWW_ROOT . '../tmp'),
            'baseUrl' => '',
        ]);

        $attachmentsTable = $this->fetchTable('Trois/Attachment.Attachments');
        $attachments = $attachmentsTable
            ->find()
            ->select(['id', 'name', 'profile', 'path', 'type', 'subtype', 'size'])
            ->where([
                'type' => 'image',
                'subtype IN' => ['gif', 'png', 'jpeg'],
            ])
            ->toArray();

        $io->info('Extract ' . count($attachments) . ' files');
        $count = 0;

        foreach ($attachments as $attachment) {
            $io->out('Processing file: ' . $attachment->name);

            $profile = $attachment->profile;
            if (!Configure::check('Trois/Attachment.profiles.' . $profile)) {
                continue;
            }

            $contents = $this->_filesystem($profile)->read($attachment->path);
            $this->_filesystem('shell_script')->write($attachment->name, $contents);
            unset($contents);

            $image_info = getimagesize(WWW_ROOT . '../tmp/' . $attachment->name);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            $attachment->set('width', $image_width);
            $attachment->set('height', $image_height);

            $this->_filesystem('shell_script')->delete($attachment->name);

            if ($attachmentsTable->save($attachment)) {
                $io->success('Saved file: ' . $attachment->name);
                $count++;
            } else {
                $io->error('Error saving file: ' . $attachment->name);
            }
        }

        $io->hr();
        $io->info($count . ' files were updated on total ' . count($attachments) . '!');

        return static::CODE_SUCCESS;
    }
}
