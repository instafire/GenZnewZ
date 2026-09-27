<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchEnginePingService
{
    public function notifyOnPublish(string $url): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->regenerateSitemapsThrottled();
        $this->submitToIndexNow($url);
        $this->pingSitemaps();
    }

    protected function submitToIndexNow(string $url): void
    {
        try {
            $indexNow = app(IndexNowService::class);
            $indexNow->submit($url, 'updated');
        } catch (\Throwable $e) {
            Log::warning('Search ping: IndexNow submission failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function regenerateSitemapsThrottled(): void
    {
        // Avoid expensive regeneration for bursts; once every 5 minutes is enough.
        $lockKey = 'seo:sitemap_regen_lock';
        if (! Cache::add($lockKey, true, now()->addMinutes(5))) {
            return;
        }

        try {
            Artisan::call('sitemap:generate', ['--type' => 'all']);
            Log::info('Search ping: sitemaps regenerated after publish');
        } catch (\Throwable $e) {
            Log::warning('Search ping: sitemap generation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function pingSitemaps(): void
    {
        Log::debug('Search ping: skipping deprecated sitemap ping endpoints and relying on sitemap regeneration plus IndexNow.');
    }

    protected function isEnabled(): bool
    {
        return (bool) env('SEO_PING_ON_PUBLISH', true);
    }
}
