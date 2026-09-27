<?php

namespace Botble\Theme\Supports;

use Botble\Base\Facades\BaseHelper;
use Botble\Sitemap\Sitemap;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class SiteMapManager
{
    protected array $keys = ['sitemap', 'pages'];

    protected array $removedKeys = [];

    protected string $extension = 'xml';

    protected string $defaultDate = '2025-04-17 00:00';

    protected array $patterns = [];

    public function __construct(protected Sitemap $siteMap)
    {
        // Register common sitemap patterns
        $this->registerPattern(
            'monthly-archive',
            '((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])(?:-page-(?<page>\d+))?'
        );
    }

    public function init(?string $prefix = null, string $extension = 'xml'): self
    {
        // create new site map object
        $this->siteMap = app('sitemap');
        
        // SEO Optimization: Use shorter cache time (5 minutes) for fresh content
        // or disable cache if configured
        $cacheEnabled = setting('enable_cache_site_map', false); // Default to false for SEO
        $cacheDuration = setting('cache_time_site_map', 5); // Default 5 minutes instead of 60
        
        $this->siteMap->setCache('cache_site_map_key' . $prefix . $extension, $cacheDuration, $cacheEnabled);

        // Always add homepage to pages sitemap for SEO
        if ($prefix == 'pages') {
            $this->add(BaseHelper::getHomepageUrl() ?: url('/'), Carbon::now()->toDateTimeString(), '1.0', 'daily');
        }

        $this->extension = $extension;

        if (! $prefix) {
            $this->addSitemap($this->route('pages'));
        }

        return $this;
    }

    public function addSitemap(string $loc, ?string $lastModified = null): self
    {
        $removedLoc = array_map(fn ($item) => $this->route($item), $this->removedKeys);

        if (! $this->isCached() && ! in_array($loc, $removedLoc)) {
            $this->siteMap->addSitemap($loc, $lastModified ?: $this->defaultDate);
        }

        return $this;
    }

    public function route(?string $key = null): string
    {
        return route('public.sitemap.index', [$key, $this->extension]);
    }

    public function add(string $url, ?string $date = null, string $priority = '1.0', string $sequence = 'daily'): self
    {
        if (! $this->isCached()) {
            $this->siteMap->add($url, $date ?: $this->defaultDate, $priority, $sequence);
        }

        return $this;
    }

    public function isCached(): bool
    {
        return $this->siteMap->isCached();
    }

    public function getSiteMap(): Sitemap
    {
        return $this->siteMap;
    }

    public function render(string $type = 'xml'): Response
    {
        // show your site map (options: 'xml' (default), 'html', 'txt', 'ror-rss', 'ror-rdf')
        return $this->siteMap->render($type);
    }

    public function getKeys(): array
    {
        return array_filter($this->keys, fn ($item) => ! in_array($item, $this->removedKeys));
    }

    public function registerKey(string|array $key, ?string $value = null): self
    {
        if (is_array($key)) {
            $this->keys = array_merge($this->keys, $key);
        } else {
            $this->keys[$key] = $value ?: $key;
        }

        return $this;
    }

    public function removeKey(array|string $key): self
    {
        $this->removedKeys = [
            ...$this->removedKeys,
            ...(array) $key,
        ];

        return $this;
    }

    public function allowedExtensions(): array
    {
        $extensions = ['xml', 'xml-mobile', 'html', 'txt', 'ror-rss', 'ror-rdf', 'google-news'];

        $slugPostfix = SlugHelper::getPublicSingleEndingURL();

        if (! $slugPostfix) {
            return $extensions;
        }

        $slugPostfix = trim($slugPostfix, '.');

        return array_filter($extensions, fn ($item) => $item != $slugPostfix);
    }

    /**
     * Get the number of items per page for pagination
     */
    public function getItemsPerPage(): int
    {
        return (int) setting('sitemap_items_per_page', 1000);
    }

    /**
     * Register a named pattern for reuse across plugins
     */
    public function registerPattern(string $name, string $pattern): self
    {
        $this->patterns[$name] = $pattern;

        return $this;
    }

    /**
     * Get a registered pattern by name
     */
    public function getPattern(string $name): ?string
    {
        return $this->patterns[$name] ?? null;
    }

    /**
     * Create a sitemap key using a registered pattern
     */
    public function generateKey(string $prefix, string $patternName, array $params = []): string
    {
        $pattern = $this->getPattern($patternName);

        if (! $pattern) {
            return $prefix;
        }

        $key = $prefix;

        foreach ($params as $placeholder => $value) {
            $pattern = str_replace(':' . $placeholder, $value, $pattern);
        }

        return $key . '-' . $pattern;
    }

    /**
     * Register sitemap keys with a standard pattern
     */
    public function registerSitemapWithPattern(string $prefix, string $patternName = 'monthly-archive'): self
    {
        if ($patternName && isset($this->patterns[$patternName])) {
            $this->registerKey($prefix . '-' . $this->patterns[$patternName]);
        } else {
            $this->registerKey($prefix);
        }

        return $this;
    }

    /**
     * Check if key contains pagination pattern
     */
    public function extractPaginationData(string $key, string $pattern): ?array
    {
        if (preg_match($pattern, $key, $matches)) {
            $page = (int) (Arr::get($matches, 'page', 1));

            $itemsPerPage = $this->getItemsPerPage();

            return [
                'matches' => $matches,
                'page' => $page,
                'offset' => ($page - 1) * $itemsPerPage,
                'limit' => $itemsPerPage,
            ];
        }

        return null;
    }

    /**
     * Extract pagination data using a registered pattern
     */
    public function extractPaginationDataByPattern(string $key, string $prefix, string $patternName): ?array
    {
        $pattern = $this->getPattern($patternName);

        if (! $pattern) {
            return null;
        }

        $fullPattern = '/^' . preg_quote($prefix, '/') . '-' . $pattern . '$/';

        return $this->extractPaginationData($key, $fullPattern);
    }

    /**
     * Create paginated sitemaps for a collection with many items
     */
    public function createPaginatedSitemaps(string $baseKey, int $totalItems, ?string $lastModified = null): void
    {
        $totalPages = ceil($totalItems / $this->getItemsPerPage());

        if ($totalPages <= 1) {
            // Single sitemap (the regular case)
            $this->addSitemap($this->route($baseKey), $lastModified);
        } else {
            // Multiple paginated sitemaps
            for ($page = 1; $page <= $totalPages; $page++) {
                $paginatedKey = $baseKey . '-page-' . $page;
                $this->addSitemap($this->route($paginatedKey), $lastModified);
            }
        }
    }

    /**
     * Register monthly archive sitemaps
     *
     * @param string $prefix The base prefix for the archive (e.g. 'products', 'blog-posts')
     * @param int $yearsBack Number of years back to register (default: 3)
     * @param int $maxPagesPerMonth Maximum number of pagination pages to register per month (default: 10)
     * @return self
     */
    public function registerMonthlyArchives(string $prefix, int $yearsBack = 3, int $maxPagesPerMonth = 10): self
    {
        // Register the base key
        $this->registerKey($prefix);

        // Register both unpaginated and paginated patterns
        // This is the simplest approach that ensures route matching works reliably
        // Non-regex keys for common cases
        $currentYear = (int) date('Y');
        for ($year = $currentYear - $yearsBack; $year <= $currentYear + 1; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                $baseKey = "{$prefix}-{$year}-{$formattedMonth}";
                $this->registerKey($baseKey);

                // Explicitly register paginated versions
                for ($page = 1; $page <= $maxPagesPerMonth; $page++) {
                    $this->registerKey("{$baseKey}-page-{$page}");
                }
            }
        }

        return $this;
    }

    /**
     * Get regex pattern for route matching that supports complex keys with pagination
     */
    public function getKeysRegex(): string
    {
        $keys = $this->getKeys();

        // Add base patterns
        $patterns = [];
        foreach ($keys as $key) {
            // If this is a regex pattern, use it directly
            if (str_contains($key, '(')) {
                $patterns[] = $key;
            } else {
                // Otherwise, escape it for literal matching
                $patterns[] = preg_quote($key, '/');
            }
        }

        // Always include blog-posts monthly archive pattern for SEO
        // This ensures all monthly sitemaps work even before keys are registered
        $blogPostsPattern = 'blog-posts-((?:19|20|21|22|23|24|25|26)\d{2})-(0?[1-9]|1[012])(?:-page-(\d+))?';
        if (! in_array($blogPostsPattern, $patterns)) {
            $patterns[] = $blogPostsPattern;
        }

        // Include categories and tags patterns
        $categoriesPattern = 'blog-categories';
        if (! in_array(preg_quote($categoriesPattern, '/'), $patterns)) {
            $patterns[] = preg_quote($categoriesPattern, '/');
        }

        $tagsPattern = 'blog-tags';
        if (! in_array(preg_quote($tagsPattern, '/'), $patterns)) {
            $patterns[] = preg_quote($tagsPattern, '/');
        }

        return '^(?:' . implode('|', $patterns) . ')$';
    }
}
