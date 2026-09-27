<?php

namespace Botble\Sitemap\Commands;

use Botble\Sitemap\Services\IndexNowService;
use Illuminate\Console\Command;

class IndexNowSubmissionCommand extends Command
{
    protected $signature = 'sitemap:indexnow {--url= : Custom sitemap URL to submit} {--generate-key : Generate a new API key}';

    protected $description = 'Submit sitemap to search engines using IndexNow protocol';

    public function __construct(protected IndexNowService $indexNowService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('generate-key')) {
            return $this->generateApiKey();
        }

        if (! $this->indexNowService->isEnabled()) {
            $this->error('IndexNow is disabled. Enable it in the sitemap settings.');

            return self::FAILURE;
        }

        if (! $this->indexNowService->getApiKey()) {
            $this->error('IndexNow API key is not configured. Generate one with --generate-key option.');

            return self::FAILURE;
        }

        $sitemapUrl = $this->option('url') ?: url('sitemap.xml');

        $this->components->info("Submitting sitemap to search engines via IndexNow: {$sitemapUrl}");
        $this->newLine();

        $results = $this->indexNowService->submitSitemap($sitemapUrl);

        foreach ($results as $engine => $result) {
            if ($result['status'] === 'success') {
                $this->info("✓ {$engine}: {$result['message']}");
            } else {
                $this->error("✗ {$engine}: {$result['message']}");
            }
        }

        $this->newLine();
        $this->components->info('IndexNow submission completed.');

        return self::SUCCESS;
    }

    protected function generateApiKey(): int
    {
        $key = $this->indexNowService->generateApiKey();

        $this->components->info("Generated new IndexNow API key: {$key}");
        $this->newLine();

        $keyFileName = $this->indexNowService->getApiKeyFileName();
        $keyFileContent = $this->indexNowService->getApiKeyFileContent();

        $this->components->warn("IMPORTANT: You must create a file named '{$keyFileName}' in your website root.");
        $this->components->warn("The file should contain only this text: {$keyFileContent}");
        $this->components->warn('The file should be accessible at: ' . url($keyFileName));

        return self::SUCCESS;
    }
}
