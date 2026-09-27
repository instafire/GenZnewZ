<?php

namespace Botble\Language\Commands;

use Botble\Base\Commands\Traits\ValidateCommandInput;
use Botble\Base\Supports\Language;
use Botble\Language\Models\Language as LanguageModel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('cms:language:add', 'Add a new language to the system')]
class AddLanguageCommand extends Command implements PromptsForMissingInput
{
    use ValidateCommandInput;

    public function handle(): int
    {
        try {
            $languageId = $this->argument('language_id');
            $isDefault = $this->option('default');
            $order = $this->option('order');

            // Get language data from predefined list
            $languages = Language::getListLanguages();

            if (! isset($languages[$languageId])) {
                $this->components->error(sprintf(
                    'Language ID "%s" is not valid. Available language IDs: %s',
                    $languageId,
                    implode(', ', array_keys($languages))
                ));

                return self::FAILURE;
            }

            $language = $languages[$languageId];

            // Check if language already exists
            if ($this->languageExists($language[1], $languageId)) {
                $this->components->error(trans('plugins/language::language.added_already'));

                return self::FAILURE;
            }

            // Ensure directories exist
            $this->ensureDirectoriesExist();

            // Import locale if missing
            $this->importLocaleIfMissing($language[0]);

            // Create the language
            $languageModel = $this->createLanguage(
                $language[2],
                $language[0],
                $language[1],
                $language[4],
                $language[3] === 'rtl',
                $order,
                $isDefault
            );

            // Clear routes cache
            $this->clearRoutesCache();

            $this->components->info(sprintf('Language "%s" has been added successfully!', $language[2]));

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function languageExists(string $code, string $languageId): bool
    {
        return LanguageModel::query()
            ->where('lang_code', $code)
            ->orWhere('lang_locale', $languageId)
            ->exists();
    }

    protected function ensureDirectoriesExist(): void
    {
        File::ensureDirectoryExists(lang_path('vendor'));

        if (! File::isWritable(lang_path()) || ! File::isWritable(lang_path('vendor'))) {
            throw new Exception(
                trans('plugins/translation::translation.folder_is_not_writeable', ['lang_path' => lang_path()])
            );
        }
    }

    protected function importLocaleIfMissing(string $locale): void
    {
        $defaultLocale = lang_path($locale);

        if (File::exists($defaultLocale)) {
            return;
        }

        File::copyDirectory(lang_path('en'), $defaultLocale);

        $this->components->info(sprintf('Created locale folder for "%s"', $locale));
    }

    protected function createLanguage(
        string $name,
        string $locale,
        string $code,
        string $flag,
        bool $isRtl,
        int $order,
        bool $isDefault
    ): LanguageModel {
        if ($isDefault) {
            LanguageModel::query()->where('lang_is_default', 1)->update(['lang_is_default' => 0]);
        }

        // If no languages exist, make this one default
        if (! LanguageModel::query()->exists()) {
            $isDefault = true;
        }

        $language = LanguageModel::query()->create([
            'lang_name' => $name,
            'lang_locale' => $locale,
            'lang_code' => $code,
            'lang_flag' => $flag,
            'lang_is_rtl' => $isRtl,
            'lang_order' => $order,
            'lang_is_default' => $isDefault,
        ]);

        return $language;
    }

    protected function clearRoutesCache(): void
    {
        $this->call('route:clear');
    }

    protected function configure(): void
    {
        $this
            ->addArgument('language_id', InputArgument::REQUIRED, 'The ID of the language (e.g. en_US)')
            ->addOption('order', null, InputOption::VALUE_OPTIONAL, 'The order of the language', 0)
            ->addOption('default', null, InputOption::VALUE_NONE, 'Whether the language is default');
    }
}
