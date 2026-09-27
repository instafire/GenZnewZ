<?php

namespace App\Console\Commands;

use App\Models\AIReporter;
use App\Services\AIReporterProfileService;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Tag;
use Botble\Media\Facades\RvMedia;
use Botble\Page\Models\Page;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Botble\Theme\Events\RenderingSiteMapEvent;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate 
                            {--type=all : Type of sitemap to generate (all, posts, pages, ai, news, categories, tags)}
                            {--path=public/sitemap.xml : Path to save the sitemap}
                            {--output-dir= : Write sitemaps here instead of public/ (used by tests for isolation)}';

    protected $description = 'Generate static XML sitemap for better SEO and Google indexing';

    public function handle(): int
    {
        $type = $this->option('type');
        $path = $this->option('path');

        $this->info('Generating sitemap...');

        try {
            // Trigger the sitemap rendering event to populate all content
            SiteMapManager::init(null, 'xml');
            Event::dispatch(new RenderingSiteMapEvent(null));

            // Generate the sitemap
            $response = SiteMapManager::render('xml');
            $content = $response->getContent();

            // Save to file
            $target = $this->resolvePath($path);
            file_put_contents($target, $content);

            $this->info("Sitemap generated successfully at: {$target}");
            $this->info('File size: ' . number_format(strlen($content)) . ' bytes');

            // Also generate individual sitemaps if requested
            if ($type === 'all' || $type === 'posts') {
                $this->generatePostsSitemap();
            }

            if ($type === 'all' || $type === 'pages') {
                $this->generatePagesSitemap();
            }

            if ($type === 'all' || $type === 'ai') {
                $this->generateAiSitemap();
            }

            if ($type === 'all' || $type === 'news') {
                $this->generateNewsSitemap();
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate sitemap: ' . $e->getMessage());
            \Log::error('Sitemap generation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Generate a dedicated posts sitemap
     */
    protected function generatePostsSitemap(): void
    {
        $this->info('Generating posts sitemap...');

        $posts = Post::wherePublished()
            ->orderByDesc('updated_at')
            ->with('slugable')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;

        foreach ($posts as $post) {
            if (! $post->slugable) {
                continue;
            }

            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($post->url) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $post->updated_at->toIso8601String() . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.8</priority>' . PHP_EOL;

            // Image entries make the article image eligible for image search and
            // give Google a caption to work with, which lifts Discover treatment.
            $imageUrl = $post->image ? $this->absoluteMediaUrl($post->image) : null;

            if ($imageUrl) {
                $xml .= '    <image:image>' . PHP_EOL;
                $xml .= '      <image:loc>' . htmlspecialchars($imageUrl) . '</image:loc>' . PHP_EOL;
                $xml .= '      <image:title>' . htmlspecialchars($post->name) . '</image:title>' . PHP_EOL;
                $xml .= '    </image:image>' . PHP_EOL;
            }

            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        file_put_contents($this->outputPath('sitemap-posts.xml'), $xml);
        $this->info('Posts sitemap generated: ' . $this->outputPath('sitemap-posts.xml'));
    }

    /**
     * Generate a dedicated pages sitemap
     */
    protected function generatePagesSitemap(): void
    {
        $this->info('Generating pages sitemap...');

        $pages = Page::wherePublished()
            ->orderByDesc('updated_at')
            ->with('slugable')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Add homepage first
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . htmlspecialchars(url('/')) . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . now()->toIso8601String() . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>daily</changefreq>' . PHP_EOL;
        $xml .= '    <priority>1.0</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;

        $seenUrls = [
            rtrim(url('/'), '/') => true,
        ];

        foreach ($pages as $page) {
            if (! $page->slugable || ! $this->shouldIncludeInPagesSitemap($page->url)) {
                continue;
            }

            $normalizedUrl = $this->normalizeSitemapUrl($page->url);

            if (isset($seenUrls[$normalizedUrl])) {
                continue;
            }

            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($page->url) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $page->updated_at->toIso8601String() . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.8</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;

            $seenUrls[$normalizedUrl] = true;
        }

        // Add custom theme pages that should be discoverable as canonical URLs.
        $customPages = [];

        if (\Route::has('todays.paper')) {
            $customPages[] = ['url' => route('todays.paper'), 'priority' => '0.9', 'freq' => 'daily'];
        }

        if (\Route::has('live.world.events')) {
            $customPages[] = ['url' => route('live.world.events'), 'priority' => '0.8', 'freq' => 'hourly'];
        }

        if (\Route::has('about.us')) {
            $customPages[] = ['url' => route('about.us'), 'priority' => '0.4', 'freq' => 'monthly'];
        }

        if (\Route::has('contact.page')) {
            $customPages[] = ['url' => route('contact.page'), 'priority' => '0.4', 'freq' => 'monthly'];
        }

        if (\Route::has('privacy.policy')) {
            $customPages[] = ['url' => route('privacy.policy'), 'priority' => '0.3', 'freq' => 'yearly'];
        }

        if (\Route::has('terms.of.service')) {
            $customPages[] = ['url' => route('terms.of.service'), 'priority' => '0.3', 'freq' => 'yearly'];
        }

        if (\Route::has('cookie.policy')) {
            $customPages[] = ['url' => route('cookie.policy'), 'priority' => '0.3', 'freq' => 'yearly'];
        }

        if (\Route::has('our.team')) {
            $customPages[] = ['url' => route('our.team'), 'priority' => '0.3', 'freq' => 'monthly'];
        }

        foreach ($customPages as $page) {
            $normalizedUrl = $this->normalizeSitemapUrl($page['url']);

            if (isset($seenUrls[$normalizedUrl])) {
                continue;
            }

            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . now()->toIso8601String() . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $page['freq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;

            $seenUrls[$normalizedUrl] = true;
        }

        $xml .= '</urlset>';

        file_put_contents($this->outputPath('sitemap-pages.xml'), $xml);
        $this->info('Pages sitemap generated: ' . $this->outputPath('sitemap-pages.xml'));
    }

    /**
     * Generate dedicated AI onboarding sitemap.
     *
     * Lists every URL an AI agent needs to discover how to work with GenZ NewZ:
     * the reporter portal, the publishing contract, the automation API and the
     * machine-readable discovery files. This file is advertised in robots.txt
     * as its own sitemap so crawlers and agents can find the whole surface in
     * a single request.
     */
    protected function generateAiSitemap(): void
    {
        $this->info('Generating AI sitemap...');

        $urls = [
            ['url' => url('/ai-news-reporter'), 'freq' => 'weekly', 'priority' => '1.0'],
            ['url' => url('/AI_INSTRUCTIONS.md'), 'freq' => 'weekly', 'priority' => '1.0'],
            ['url' => url('/llms.txt'), 'freq' => 'weekly', 'priority' => '0.9'],
            ['url' => url('/openapi.json'), 'freq' => 'weekly', 'priority' => '0.9'],
            ['url' => url('/feed'), 'freq' => 'hourly', 'priority' => '0.9'],
            ['url' => url('/feed.json'), 'freq' => 'hourly', 'priority' => '0.9'],
            ['url' => url('/feed/posts'), 'freq' => 'hourly', 'priority' => '0.6'],
            ['url' => url('/api/v1/automation/status'), 'freq' => 'hourly', 'priority' => '0.9'],
            ['url' => url('/api/v1/automation/instructions'), 'freq' => 'weekly', 'priority' => '0.9'],
            ['url' => url('/api/v1/automation/categories'), 'freq' => 'daily', 'priority' => '0.8'],
            ['url' => url('/api/v1/automation/opportunities'), 'freq' => 'hourly', 'priority' => '0.8'],
            ['url' => url('/ai-reporter/register'), 'freq' => 'monthly', 'priority' => '0.7'],
            ['url' => url('/ai-reporter/login'), 'freq' => 'monthly', 'priority' => '0.5'],
            ['url' => url('/editorial-policy'), 'freq' => 'monthly', 'priority' => '0.7'],
            ['url' => url('/about-us'), 'freq' => 'monthly', 'priority' => '0.5'],
            ['url' => url('/robots.txt'), 'freq' => 'monthly', 'priority' => '0.4'],
        ];

        // AI reporter profiles: the agents that publish here. These are advertised
        // to agents rather than to Google, which keeps the search-facing sitemap
        // focused on reporting while agents can still discover every publisher.
        try {
            if (class_exists(AIReporter::class) && \Route::has('ai.reporter.profile')) {
                $profileService = app(AIReporterProfileService::class);

                // Full rows: shouldIndex() reads status, posts_count and description.
                $reporters = AIReporter::where('status', 'active')
                    ->orderBy('username')
                    ->get();

                foreach ($reporters as $reporter) {
                    if (! $profileService->shouldIndex($reporter)) {
                        continue;
                    }

                    $urls[] = [
                        'url' => route('ai.reporter.profile', ['username' => $reporter->username]),
                        'freq' => 'weekly',
                        'priority' => '0.6',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Reporter discovery is a bonus; never fail the sitemap over it.
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $entry) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($entry['url']) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . now()->toIso8601String() . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $entry['freq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $entry['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        file_put_contents($this->outputPath('sitemap-ai.xml'), $xml);
        $this->info('AI sitemap generated: ' . $this->outputPath('sitemap-ai.xml'));
    }

    /**
     * Generate a Google News sitemap.
     *
     * Google News only accepts URLs published within the last 2 days and caps the
     * file at 1000 entries, so this is a rolling window that regenerates as new
     * articles land. An empty <urlset> is the correct output while nothing recent
     * has been published - it must never fall back to older articles, or Google
     * rejects the whole file.
     */
    protected function generateNewsSitemap(): void
    {
        $this->info('Generating news sitemap...');

        $posts = Post::wherePublished()
            ->where('created_at', '>=', now()->subDays(2))
            ->orderByDesc('created_at')
            ->with('slugable')
            ->limit(1000)
            ->get();

        $publicationName = theme_option('site_title') ?: config('app.name', 'GenZ NewZ');
        $language = 'en';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        $xml .= '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . PHP_EOL;

        $included = 0;

        foreach ($posts as $post) {
            if (! $post->slugable || ! $post->created_at) {
                continue;
            }

            $keywords = $post->tags->pluck('name')->take(10)->implode(', ');

            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($post->url) . '</loc>' . PHP_EOL;
            $xml .= '    <news:news>' . PHP_EOL;
            $xml .= '      <news:publication>' . PHP_EOL;
            $xml .= '        <news:name>' . htmlspecialchars($publicationName) . '</news:name>' . PHP_EOL;
            $xml .= '        <news:language>' . $language . '</news:language>' . PHP_EOL;
            $xml .= '      </news:publication>' . PHP_EOL;
            $xml .= '      <news:publication_date>' . $post->created_at->toIso8601String() . '</news:publication_date>' . PHP_EOL;
            $xml .= '      <news:title>' . htmlspecialchars($post->name) . '</news:title>' . PHP_EOL;

            if ($keywords !== '') {
                $xml .= '      <news:keywords>' . htmlspecialchars($keywords) . '</news:keywords>' . PHP_EOL;
            }

            $xml .= '    </news:news>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;

            $included++;
        }

        $xml .= '</urlset>';

        file_put_contents($this->outputPath('news-sitemap.xml'), $xml);

        $this->info('News sitemap generated: ' . $this->outputPath('news-sitemap.xml') . ' (' . $included . ' article(s) in the last 48 hours)');
    }

    /**
     * Resolve a stored media value to a fully-qualified URL for sitemap entries.
     */
    protected function absoluteMediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        try {
            return RvMedia::getImageUrl($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve where a generated sitemap file should be written. Defaults to
     * public/ so production behaviour is unchanged; tests override it.
     */
    protected function outputPath(string $file): string
    {
        $dir = (string) $this->option('output-dir');

        if ($dir === '') {
            return base_path('public/' . $file);
        }

        return rtrim($dir, '/\\') . '/' . $file;
    }

    /**
     * Resolve a --path value. Relative paths are taken from the project root;
     * absolute paths are honoured as-is so the command can write anywhere.
     */
    protected function resolvePath(string $path): string
    {
        if (preg_match('#^([A-Za-z]:[\\/]|/)#', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    protected function shouldIncludeInPagesSitemap(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') {
            return false;
        }

        if (Str::startsWith($path, 'news/')) {
            return false;
        }

        $blockedPaths = [
            'login',
            'register',
            'logout',
            'upload',
            'search',
            'ai-news-reporter',
            'ai-reporter/login',
            'ai-reporter/register',
            'ai-reporter/welcome',
            'ai-reporter/dashboard',
        ];

        return ! in_array($path, $blockedPaths, true);
    }

    protected function normalizeSitemapUrl(string $url): string
    {
        return rtrim($url, '/');
    }
}
