<?php
declare(strict_types=1);

namespace Trois\Attachment\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;

class CreateMissingTranslationsCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Create missing translations for attachments')
            ->addArgument('locales', [
                'help' => 'Locales to create translations for (space separated, e.g., "en_US de_DE")',
                'required' => true,
            ]);

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
        // Check config
        if (!Configure::read('Trois/Attachment.translate')) {
            $io->error('You did not set Attachment.translate to "true"!');
            return static::CODE_ERROR;
        }

        // Check args
        $localesArg = $args->getArgument('locales');
        if (empty($localesArg)) {
            $io->error('Need to pass at least one locale, e.g., "bin/cake at_create_missing_translations en_US de_DE"');
            return static::CODE_ERROR;
        }

        $locales = explode(' ', $localesArg);

        // Build query
        $attachmentsTable = $this->fetchTable('Trois/Attachment.Attachments');
        $query = $attachmentsTable->find()->select([
            'Attachments.id',
            'Attachments.title',
            'Attachments.description',
        ]);

        // Ensure locales exist and add if exists...
        $i18nTable = $this->fetchTable('I18n');
        foreach ($locales as $locale) {
            $firstLocaleRow = $i18nTable->find()->where(['locale' => $locale])->first();
            if (empty($firstLocaleRow)) {
                $io->error('Locale "' . $locale . '" is not present in table i18n!');
                return static::CODE_ERROR;
            }

            $alias = Inflector::camelize($locale);
            $query->select([$alias . '.id'])
                ->leftJoin([$alias => 'i18n'], [
                    $alias . '.model' => 'Attachments',
                    $alias . '.foreign_key = Attachments.id',
                    $alias . '.locale' => $locale,
                    $alias . '.field' => 'title',
                ])
                ->where([$alias . '.id IS NULL']);
        }

        // To array
        $lonelyChilds = $query->toArray();
        if (empty($lonelyChilds)) {
            $io->info('There is not an attachment without its related translations :)');
            return static::CODE_SUCCESS;
        }

        // Create related records
        $entities = [];
        $fields = ['title', 'description'];
        foreach ($lonelyChilds as $lonelyChild) {
            foreach ($locales as $locale) {
                foreach ($fields as $field) {
                    $entities[] = [
                        'locale' => $locale,
                        'model' => 'Attachments',
                        'foreign_key' => $lonelyChild->id,
                        'field' => $field,
                        'content' => '',
                    ];
                }
            }
        }

        $entities = $i18nTable->newEntities($entities);
        $results = $i18nTable->saveMany($entities);

        if (empty($results)) {
            $io->error('No record was added, an error occurred!');
            return static::CODE_ERROR;
        } else {
            $io->info(count($results) . ' records were added for ' . count($lonelyChilds) . ' attachments without translations.');
        }

        return static::CODE_SUCCESS;
    }
}
