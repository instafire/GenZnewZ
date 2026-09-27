<?php

namespace App\Console\Commands;

use App\Services\IndexNowService;
use Botble\Blog\Models\Post;
use Botble\Page\Models\Page;
use Illuminate\Console\Command;

class PingSearchEngines extends Command
{
    protected $signature = 'seo:ping-search-engines
                            {--regenerate-sitemaps : Regenerate sitemaps before pinging}
                            {--limit=200 : Max number of recent URLs to submit to IndexNow}
                            {--hours=48 : Only submit content changed within this many hours}';

    protected $description = 'Ping search engines with sitemap updates and submit recent URLs to IndexNow';

    public function handle(IndexNowService $indexNow): int
    {
        if ($this->option('regenerate-sitemaps')) {
            $this->info('Regenerating sitemaps...');
            $this->call('sitemap:generate', ['--type' => 'all']);
        }

        $sitemaps = [
            url('/sitemap.xml'),
            url('/sitemap-posts.xml'),
            url('/sitemap-pages.xml'),
            url('/sitemap-ai.xml'),
        ];

        $this->pingSitemapEndpoints($sitemaps);
        $this->submitRecentUrlsToIndexNow($indexNow);

        $this->info('Search engine ping completed.');

        return self::SUCCESS;
    }

    protected function pingSitemapEndpoints(array $sitemaps): void
    {
        if ($sitemaps !== []) {
            $this->line('Skipping deprecated Google/Bing sitemap ping endpoints; relying on regenerated sitemaps and IndexNow submissions.');
        }
    }

    protected function submitRecentUrlsToIndexNow(IndexNowService $indexNow): void
    {
        if (! $indexNow->isEnabled()) {
            $this->warn('IndexNow is disabled. Skipping URL submission.');
            return;
        }

        $limit = max(10, (int) $this->option('limit'));
        $urls = collect([url('/')]);

        if (\Route::has('ai.reporter.register')) {
            $urls->push(route('ai.reporter.register'));
        }

        if (\Route::has('ai.reporter.login')) {
            $urls->push(route('ai.reporter.login'));
        }

        // Only submit what actually changed inside the window. Submitting the same
        // unchanged URLs on every run (this task is scheduled hourly) wastes the
        // submission quota and trains IndexNow endpoints to deprioritise us.
        $changedSince = now()->subHours(max(1, (int) $this->option('hours')));

        if (class_exists(Post::class)) {
            $postUrls = Post::where('status', 'published')
                ->where('updated_at', '>=', $changedSince)
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get()
                ->pluck('url')
                ->filter();

            $urls = $urls->merge($postUrls);
        }

        if (class_exists(Page::class)) {
            $pageUrls = Page::where('status', 'published')
                ->where('updated_at', '>=', $changedSince)
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get()
                ->pluck('url')
                ->filter();

            $urls = $urls->merge($pageUrls);
        }

        $this->line('Content changed in the last ' . max(1, (int) $this->option('hours')) . 'h: ' . ($urls->count() - 1) . ' URL(s) plus core agent pages.');

        $urls = $urls->unique()->values()->all();

        if (empty($urls)) {
            $this->warn('No URLs found for IndexNow submission.');
            return;
        }

        $ok = $indexNow->submitBulk($urls);
        if ($ok) {
            $this->info('IndexNow submission completed for ' . count($urls) . ' URLs.');
        } else {
            $this->warn('IndexNow submission completed with warnings. Check logs.');
        }
    }
}
