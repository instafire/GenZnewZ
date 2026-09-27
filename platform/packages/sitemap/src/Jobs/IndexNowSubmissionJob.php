<?php

namespace Botble\Sitemap\Jobs;

use Botble\Sitemap\Services\IndexNowService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class IndexNowSubmissionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public ?string $sitemapUrl = null)
    {
    }

    public function handle(IndexNowService $indexNowService): void
    {
        if (! $indexNowService->isEnabled()) {
            Log::info('IndexNow is disabled, skipping job');

            return;
        }

        if (! $indexNowService->getApiKey()) {
            Log::warning('IndexNow API key is not configured, skipping job');

            return;
        }

        try {
            $indexNowService->submitSitemap($this->sitemapUrl);
        } catch (Exception $e) {
            Log::error('IndexNow submission job failed', [
                'sitemap_url' => $this->sitemapUrl ?: url('sitemap.xml'),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('IndexNow submission job failed permanently', [
            'sitemap_url' => $this->sitemapUrl ?: url('sitemap.xml'),
            'error' => $exception->getMessage(),
        ]);
    }
}
