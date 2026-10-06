<?php

namespace App\Services;

use App\Jobs\FetchRssFeedsJob;
use Illuminate\Support\Facades\Cache;

class LiveWorldEventsServiceOptimized
{
    /**
     * RSS Feeds organized by category
     */
    protected array $feeds = [
        'Breaking News' => [
            'https://abcnews.go.com/abcnews/topstories',
            'https://feeds.foxnews.com/foxnews/latest',
            'https://globalnews.ca/feed/',
            'https://www.rte.ie/news/rss/news-headlines.xml',
        ],
        'Technology' => [
            'https://techcrunch.com/feed/',
            'https://www.theverge.com/rss/index.xml',
            'https://arstechnica.com/rss/',
        ],
        'Science & Space' => [
            'https://www.space.com/rss/',
            'https://www.sciencedaily.com/rss/',
            'https://futurism.com/feed/',
        ],
        'Business' => [
            'https://fortune.com/feed/',
            'https://www.fastcompany.com/rss/',
        ],
        'Programming' => [
            'https://github.blog/feed/',
            'https://stackoverflow.blog/feed/',
            'https://dev.to/feed',
        ],
        'Gaming' => [
            'https://www.pcgamer.com/rss/',
        ],
        'Entertainment' => [
            'https://variety.com/rss/',
            'https://www.hollywoodreporter.com/rss/',
        ],
        'Sports' => [
            'https://www.espn.com/espn/rss/news',
            'https://www.skysports.com/rss/12040',
        ],
    ];

    /**
     * Get all categories (fast, no RSS fetching)
     */
    public function getCategories(): array
    {
        return array_keys($this->feeds);
    }

    /**
     * Get cached headlines for a specific category (instant)
     */
    public function getCachedCategoryHeadlines(string $category): ?array
    {
        $cacheKey = 'live_events_category_' . md5($category);
        $cached = Cache::get($cacheKey);
        
        // If cache is empty, return fallback data immediately
        if (!$cached) {
            return [
                'category' => $category,
                'headlines' => $this->getFallbackHeadlines($category),
                'fetched_at' => now()->toIso8601String(),
                'is_fallback' => true,
            ];
        }
        
        return $cached;
    }

    /**
     * Get all cached headlines (instant)
     * Returns fallback data for categories without cached data
     */
    public function getAllCachedHeadlines(): array
    {
        $allHeadlines = [];

        foreach ($this->feeds as $category => $urls) {
            $cached = $this->getCachedCategoryHeadlines($category);
            
            if ($cached && isset($cached['headlines'])) {
                $allHeadlines[$category] = $cached['headlines'];
            } else {
                // Return fallback headlines immediately
                $allHeadlines[$category] = $this->getFallbackHeadlines($category);
            }
        }

        return $allHeadlines;
    }

    /**
     * Get fallback headlines for a category
     */
    protected function getFallbackHeadlines(string $category): array
    {
        $fallbacks = [
            'Breaking News' => [
                ['title' => 'Latest breaking news updates from around the world', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Stay tuned for real-time news updates', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'News feeds updating - fresh content coming soon', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Global headlines being refreshed', 'link' => '#', 'pubDate' => now()->subMinutes(15)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Technology' => [
                ['title' => 'Latest tech news and innovation updates', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'AI and computing developments', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Gadget reviews and tech trends', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Science & Space' => [
                ['title' => 'Space exploration and astronomy news', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Scientific discoveries and research updates', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Climate and environmental science', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Business' => [
                ['title' => 'Financial markets and business news', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Economic updates and market trends', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Corporate news and industry analysis', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Programming' => [
                ['title' => 'Software development and coding news', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Open source projects and community updates', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Programming tutorials and best practices', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Gaming' => [
                ['title' => 'Latest gaming news and releases', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Esports tournaments and competitive gaming', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Game reviews and industry updates', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Entertainment' => [
                ['title' => 'Celebrity news and entertainment updates', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Movie and TV show news', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Music and cultural events', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
            'Sports' => [
                ['title' => 'Sports news and match updates', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Athlete news and transfer updates', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Tournament results and analysis', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
            ],
        ];

        return $fallbacks[$category] ?? [
            ['title' => 'News updates for ' . $category, 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
            ['title' => 'Stay tuned for latest updates', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
        ];
    }

    /**
     * Dispatch background jobs to fetch all feeds (non-blocking)
     */
    public function dispatchFeedFetchJobs(): void
    {
        foreach ($this->feeds as $category => $urls) {
            $cacheKey = 'live_events_category_' . md5($category);
            $cached = Cache::get($cacheKey);

            // Only dispatch if cache is empty or expired soon (< 3 minutes left)
            if (!$cached || $this->isCacheExpiringSoon($cacheKey)) {
                FetchRssFeedsJob::dispatch($category, $urls)
                    ->onQueue('rss-feeds'); // Use dedicated queue
            }
        }
    }

    /**
     * Check if cache is expiring soon
     */
    protected function isCacheExpiringSoon(string $cacheKey): bool
    {
        $cached = Cache::get($cacheKey);

        if (!$cached || !isset($cached['fetched_at'])) {
            return true;
        }

        $fetchedAt = \Carbon\Carbon::parse($cached['fetched_at']);
        $minutesOld = now()->diffInMinutes($fetchedAt);

        // Refresh if older than 12 minutes (cache is 15 min, refresh before expiry)
        return $minutesOld >= 12;
    }

    /**
     * Get loading progress (how many categories have real data)
     */
    public function getLoadingProgress(): array
    {
        $totalCategories = count($this->feeds);
        $loadedCategories = 0;

        foreach ($this->feeds as $category => $urls) {
            $cacheKey = 'live_events_category_' . md5($category);
            $cached = Cache::get($cacheKey);
            if ($cached && !empty($cached['headlines']) && !($cached['is_fallback'] ?? false)) {
                $loadedCategories++;
            }
        }

        return [
            'total' => $totalCategories,
            'loaded' => $loadedCategories,
            'percentage' => $totalCategories > 0 ? round(($loadedCategories / $totalCategories) * 100) : 0,
            'is_complete' => $loadedCategories === $totalCategories,
        ];
    }

    /**
     * Force refresh all feeds (use sparingly)
     */
    public function forceRefreshAll(): void
    {
        // Clear all caches
        foreach ($this->feeds as $category => $urls) {
            $cacheKey = 'live_events_category_' . md5($category);
            Cache::forget($cacheKey);
        }

        // Dispatch all jobs
        $this->dispatchFeedFetchJobs();
    }

    /**
     * Get feed URLs for a category (for API)
     */
    public function getCategoryFeeds(string $category): array
    {
        return $this->feeds[$category] ?? [];
    }
}
