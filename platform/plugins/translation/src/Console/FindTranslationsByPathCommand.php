<?php

namespace Botble\Translation\Console;

use Botble\Translation\Manager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:translations:find-by-path', 'Find JSON translations by path')]
class FindTranslationsByPathCommand extends Command
{
    protected $signature = 'cms:translations:find-by-path
                            {path : The path to scan for translations}
                            {--save-to= : Optional path to save the found keys to en.json}
                            {--merge : Merge with existing translations if save-to file exists}
                            {--sort : Sort the keys alphabetically}';

    protected $description = 'Find JSON translation keys in the specified path and optionally save them to en.json';

    public function handle(Manager $manager): int
    {
        $path = $this->argument('path');
        $saveTo = $this->option('save-to');
        $merge = $this->option('merge');
        $sort = $this->option('sort');

        // Validate the path
        if (! File::exists($path)) {
            $this->components->error("Path does not exist: {$path}");

            return self::FAILURE;
        }

        if (! File::isDirectory($path)) {
            $this->components->error("Path is not a directory: {$path}");

            return self::FAILURE;
        }

        $this->components->info("Scanning for JSON translations in: {$path}");

        // Find translation keys
        $keys = $manager->findJsonTranslations($path);

        if (empty($keys)) {
            $this->components->warn('No translation keys found.');

            return self::SUCCESS;
        }

        $count = count($keys);
        $this->components->info("Found {$count} translation keys:");

        // Display found keys
        $this->displayKeys($keys);

        // Save to file if requested
        if ($saveTo) {
            $this->saveTranslations($keys, $saveTo, $merge, $sort);
        } else {
            // Ask if user wants to save
            if ($this->confirm('Do you want to save these keys to a file?')) {
                $defaultPath = 'en.json';
                $savePath = $this->ask('Enter the path to save the keys (relative to current directory)', $defaultPath);

                $mergeOption = false;
                if (File::exists($savePath)) {
                    $mergeOption = $this->confirm('File exists. Do you want to merge with existing translations?');
                }

                $sortOption = $this->confirm('Do you want to sort the keys alphabetically?', true);

                $this->saveTranslations($keys, $savePath, $mergeOption, $sortOption);
            }
        }

        return self::SUCCESS;
    }

    protected function displayKeys(array $keys): void
    {
        $this->newLine();

        if (count($keys) <= 20) {
            // Show all keys if there are 20 or fewer
            foreach ($keys as $key) {
                $this->line("  - {$key}");
            }
        } else {
            // Show first 10 and last 10 with a summary in between
            $keysList = array_values($keys);

            $this->line('  First 10 keys:');
            for ($i = 0; $i < 10; $i++) {
                $this->line("    - {$keysList[$i]}");
            }

            $remaining = count($keys) - 20;
            if ($remaining > 0) {
                $this->line("  ... and {$remaining} more keys ...");
            }

            $this->line('  Last 10 keys:');
            for ($i = count($keysList) - 10; $i < count($keysList); $i++) {
                $this->line("    - {$keysList[$i]}");
            }
        }

        $this->newLine();
    }

    protected function saveTranslations(array $keys, string $saveTo, bool $merge = false, bool $sort = false): void
    {
        $translations = [];

        // Load existing translations if merging
        if ($merge) {
            if (File::exists($saveTo)) {
                $existingContent = File::get($saveTo);
                $existingTranslations = json_decode($existingContent, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($existingTranslations)) {
                    $translations = $existingTranslations;
                    $this->components->info("Loaded existing translations from: {$saveTo}");
                } else {
                    $this->components->warn('Could not parse existing JSON file. Starting with empty translations.');
                }
            } else {
                $this->components->info('Target file does not exist. Creating new file with found translations.');
            }
        }

        // Add new keys (preserve existing values if merging)
        foreach ($keys as $key) {
            if (! isset($translations[$key])) {
                $translations[$key] = $key;
            }
        }

        // Sort if requested
        if ($sort) {
            ksort($translations);
        }

        // Ensure directory exists
        $directory = dirname($saveTo);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Save to file
        $jsonContent = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (File::put($saveTo, $jsonContent)) {
            $action = $merge && File::exists($saveTo) ? 'merged with' : 'saved to';
            $this->components->success("Successfully {$action}: {$saveTo}");
            $this->components->info('Total keys in file: ' . count($translations));
        } else {
            $this->components->error("Failed to save translations to: {$saveTo}");
        }
    }
}
