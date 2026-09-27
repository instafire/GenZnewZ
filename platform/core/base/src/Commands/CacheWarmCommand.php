<?php

namespace Botble\Base\Commands;

use Botble\Base\Supports\GoogleFonts;
use Botble\PluginManagement\PluginManifest;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:cache:warm', 'Warm up application caches for better boot performance')]
class CacheWarmCommand extends Command
{
    public function handle(PluginManifest $pluginManifest): int
    {
        $this->components->info('Warming up application caches...');

        // Warm Google Fonts cache
        $this->components->task('Loading Google Fonts', function (): void {
            GoogleFonts::getFonts();
        });

        // Warm plugin manifest cache
        $this->components->task('Generating plugin manifest', function () use ($pluginManifest): void {
            $pluginManifest->generateManifest();
        });

        // Clear and warm Laravel config cache
        $this->components->task('Caching configuration', function (): void {
            $this->callSilently('config:clear');
            $this->callSilently('config:cache');
        });

        // Clear and warm route cache
        $this->components->task('Caching routes', function (): void {
            $this->callSilently('route:clear');
            $this->callSilently('route:cache');
        });

        // Clear and warm view cache
        $this->components->task('Caching views', function (): void {
            $this->callSilently('view:clear');
            $this->callSilently('view:cache');
        });

        $this->components->success('All caches warmed successfully!');

        return self::SUCCESS;
    }
}
