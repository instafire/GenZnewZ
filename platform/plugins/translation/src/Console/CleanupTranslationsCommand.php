<?php

namespace Botble\Translation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:translations:cleanup', 'Cleanup translation files by removing extra keys and adding missing ones')]
class CleanupTranslationsCommand extends Command
{
    protected $signature = 'cms:translations:cleanup
                            {reference : The reference translation file (e.g., en.json)}
                            {target : The target translation file to cleanup (e.g., vi.json)}
                            {--backup : Create backup of target file before cleanup}
                            {--dry-run : Show what would be changed without making actual changes}
                            {--sort : Sort keys alphabetically}';

    protected $description = 'Cleanup translation files by removing keys not present in reference file and adding missing keys';

    public function handle(): int
    {
        $referencePath = $this->argument('reference');
        $targetPath = $this->argument('target');
        $createBackup = $this->option('backup');
        $dryRun = $this->option('dry-run');
        $sort = $this->option('sort');

        // Validate file paths
        if (! File::exists($referencePath)) {
            $this->components->error("Reference file not found: {$referencePath}");

            return self::FAILURE;
        }

        if (! File::exists($targetPath)) {
            $this->components->error("Target file not found: {$targetPath}");

            return self::FAILURE;
        }

        // Read and decode JSON files
        $referenceContent = File::get($referencePath);
        $targetContent = File::get($targetPath);

        $referenceData = json_decode($referenceContent, true);
        $targetData = json_decode($targetContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->components->error('Failed to decode JSON files. Please check file format.');

            return self::FAILURE;
        }

        $this->components->info("Reference file ({$referencePath}) has " . count($referenceData) . ' keys');
        $this->components->info("Target file ({$targetPath}) has " . count($targetData) . ' keys');

        // Find differences
        $extraKeys = array_diff(array_keys($targetData), array_keys($referenceData));
        $missingKeys = array_diff(array_keys($referenceData), array_keys($targetData));

        $this->newLine();
        $this->components->info('Keys in target but not in reference: ' . count($extraKeys));
        $this->components->info('Keys in reference but not in target: ' . count($missingKeys));

        if (count($extraKeys) === 0 && count($missingKeys) === 0) {
            $this->components->info('No cleanup needed. Files are already synchronized.');

            return self::SUCCESS;
        }

        // Display changes
        if (count($extraKeys) > 0) {
            $this->newLine();
            $this->components->warn('Extra keys to be removed:');
            foreach (array_slice($extraKeys, 0, 10) as $key) {
                $this->line("  - \"{$key}\"");
            }
            if (count($extraKeys) > 10) {
                $this->line('  ... and ' . (count($extraKeys) - 10) . ' more');
            }
        }

        if (count($missingKeys) > 0) {
            $this->newLine();
            $this->components->warn('Missing keys to be added (with reference values as placeholders):');
            foreach (array_slice($missingKeys, 0, 10) as $key) {
                $this->line("  - \"{$key}\"");
            }
            if (count($missingKeys) > 10) {
                $this->line('  ... and ' . (count($missingKeys) - 10) . ' more');
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->components->info('Dry run completed. No changes were made.');

            return self::SUCCESS;
        }

        // Confirm changes (skip confirmation in non-interactive mode)
        if (! $this->input->isInteractive() || $this->confirm('Do you want to proceed with the cleanup?')) {
            // Proceed with cleanup
        } else {
            $this->components->info('Cleanup cancelled.');

            return self::SUCCESS;
        }

        // Create cleaned data
        $cleanedData = [];
        foreach ($referenceData as $key => $value) {
            if (isset($targetData[$key])) {
                // Use target translation if it exists
                $cleanedData[$key] = $targetData[$key];
            } else {
                // Use reference value as placeholder for missing translations
                $cleanedData[$key] = $value;
            }
        }

        // Sort if requested
        if ($sort) {
            ksort($cleanedData);
        }

        $this->newLine();
        $this->components->info('Cleaned target file will have ' . count($cleanedData) . ' keys');

        // Create backup if requested
        if ($createBackup) {
            $backupPath = $targetPath . '.backup.' . date('Y-m-d-H-i-s');
            if (File::copy($targetPath, $backupPath)) {
                $this->components->info("Backup created: {$backupPath}");
            } else {
                $this->components->warn('Warning: Could not create backup file');
            }
        }

        // Write cleaned data
        $cleanedContent = json_encode($cleanedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (File::put($targetPath, $cleanedContent)) {
            $this->components->success("Successfully updated target file: {$targetPath}");
            $this->components->info('Removed ' . count($extraKeys) . ' extra keys');
            $this->components->info('Added ' . count($missingKeys) . ' missing keys (with reference values as placeholders)');
        } else {
            $this->components->error("Failed to write to target file: {$targetPath}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
