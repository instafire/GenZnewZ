<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsChatExternalContextService
{
    protected const CACHE_TTL_SECONDS = 600;

    protected const MAX_CATEGORIES = 2;

    protected const MAX_FEEDS_PER_CATEGORY = 2;

    protected const MAX_ITEMS_PER_FEED = 2;

    protected const MAX_ITEMS_PER_CATEGORY = 3;

    protected const MAX_TOTAL_ITEMS = 6;

    protected const HTTP_CONNECT_TIMEOUT_SECONDS = 3;

    protected const HTTP_TIMEOUT_SECONDS = 6;

    protected const USER_AGENT = 'GenZNewZNewsChat/1.0 (+https://genznewz.com)';

    public function __construct(protected LiveWorldEventsServiceOptimized $liveWorldEventsService)
    {
    }

    public function buildContext(string $userMessage): string
    {
        $categories = $this->resolveCategories($userMessage);
        $headlines = collect();

        foreach ($categories as $category) {
            $headlines = $headlines->merge($this->getCategoryHeadlines($category));
        }

        $lines = $headlines
            ->sortByDesc(fn (array $headline) => $headline['sort_at'] ?? 0)
            ->take(self::MAX_TOTAL_ITEMS)
            ->values()
            ->map(function (array $headline): string {
                $published = $headline['published_at'] ?? 'unknown';
                $url = $headline['link'] ?? 'unavailable';

                return sprintf(
                    '- [%s] %s | Source: %s | Published: %s | URL: %s',
                    $headline['category'] ?? 'News',
                    $headline['title'] ?? 'Untitled headline',
                    $headline['source'] ?? 'Unknown source',
                    $published,
                    $url
                );
            })
            ->all();

        if ($lines === []) {
            return '- No trusted external headline snapshot is currently available.';
        }

        return implode("\n", $lines);
    }

    protected function resolveCategories(string $userMessage): array
    {
        $message = Str::lower($userMessage);

        $keywordMap = [
            'Technology' => ['ai', 'tech', 'technology', 'startup', 'gadget', 'openai', 'google', 'apple', 'microsoft', 'chip', 'robot'],
            'Science & Space' => ['science', 'space', 'nasa', 'spacex', 'astronomy', 'physics', 'quantum', 'climate', 'research'],
            'Business' => ['business', 'economy', 'market', 'markets', 'finance', 'financial', 'stocks', 'stock', 'crypto', 'bitcoin', 'ethereum'],
            'Programming' => ['programming', 'developer', 'developers', 'coding', 'software', 'open source', 'github', 'framework', 'api'],
            'Gaming' => ['gaming', 'game', 'games', 'xbox', 'playstation', 'nintendo', 'esports'],
            'Entertainment' => ['entertainment', 'movie', 'movies', 'tv', 'music', 'celebrity', 'celebrities', 'hollywood', 'streaming', 'culture'],
            'Sports' => ['sports', 'sport', 'nba', 'nfl', 'mlb', 'soccer', 'football', 'tennis', 'f1', 'formula 1', 'olympics'],
        ];

        $matched = collect($keywordMap)
            ->filter(function (array $keywords) use ($message): bool {
                foreach ($keywords as $keyword) {
                    if (str_contains($message, $keyword)) {
                        return true;
                    }
                }

                return false;
            })
            ->keys()
            ->take(self::MAX_CATEGORIES)
            ->values()
            ->all();

        if ($matched !== []) {
            return $matched;
        }

        return ['Breaking News'];
    }

    protected function getCategoryHeadlines(string $category): array
    {
        $cacheKey = 'news_chat_external_headlines_' . md5($category);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($category): array {
            $liveHeadlines = $this->fetchCategoryHeadlines($category);

            if ($liveHeadlines !== []) {
                return $liveHeadlines;
            }

            $cached = $this->liveWorldEventsService->getCachedCategoryHeadlines($category);

            return $this->normalizeCachedHeadlines($cached['headlines'] ?? [], $category);
        });
    }

    protected function fetchCategoryHeadlines(string $category): array
    {
        $feedUrls = array_slice($this->liveWorldEventsService->getCategoryFeeds($category), 0, self::MAX_FEEDS_PER_CATEGORY);
        $headlines = collect();

        foreach ($feedUrls as $feedUrl) {
            $headlines = $headlines->merge($this->fetchFeedHeadlines($feedUrl, $category));
        }

        return $headlines
            ->unique(fn (array $headline) => Str::lower(($headline['title'] ?? '') . '|' . ($headline['source'] ?? '')))
            ->sortByDesc(fn (array $headline) => $headline['sort_at'] ?? 0)
            ->take(self::MAX_ITEMS_PER_CATEGORY)
            ->values()
            ->all();
    }

    protected function fetchFeedHeadlines(string $feedUrl, string $category): array
    {
        try {
            $response = Http::connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::HTTP_TIMEOUT_SECONDS)
                ->withOptions([
                    'verify' => true,
                    'allow_redirects' => true,
                ])
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                ])
                ->get($feedUrl);

            if (! $response->successful()) {
                return [];
            }

            $body = mb_convert_encoding($response->body(), 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1251,Windows-1252');
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();

            if (! $xml) {
                return [];
            }

            $items = $xml->channel->item ?? $xml->item ?? $xml->entry ?? [];
            $source = $this->extractSourceName($xml, $feedUrl);
            $headlines = [];

            foreach ($items as $item) {
                $title = $this->cleanTitle((string) ($item->title ?? ''));
                $link = $this->extractLink($item);
                $publishedAt = $this->extractPublishedAt($item);

                if ($title === '') {
                    continue;
                }

                $headlines[] = [
                    'category' => $category,
                    'title' => $title,
                    'source' => $source,
                    'link' => $link !== '' ? $link : 'unavailable',
                    'published_at' => $publishedAt['label'],
                    'sort_at' => $publishedAt['timestamp'],
                ];

                if (count($headlines) >= self::MAX_ITEMS_PER_FEED) {
                    break;
                }
            }

            return $headlines;
        } catch (\Throwable $e) {
            Log::debug('News chat external headline fetch failed.', [
                'category' => $category,
                'feed' => $feedUrl,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function normalizeCachedHeadlines(array $headlines, string $category): array
    {
        return collect($headlines)
            ->map(function (array $headline) use ($category): array {
                $publishedAt = $this->normalizeDateLabel((string) ($headline['pubDate'] ?? ''));

                return [
                    'category' => $category,
                    'title' => $this->cleanTitle((string) ($headline['title'] ?? '')),
                    'source' => $this->cleanTitle((string) ($headline['source'] ?? 'Unknown source')),
                    'link' => $this->cleanLink((string) ($headline['link'] ?? '')),
                    'published_at' => $publishedAt['label'],
                    'sort_at' => $publishedAt['timestamp'],
                ];
            })
            ->filter(fn (array $headline): bool => $headline['title'] !== '')
            ->take(self::MAX_ITEMS_PER_CATEGORY)
            ->values()
            ->all();
    }

    protected function extractSourceName(\SimpleXMLElement $xml, string $feedUrl): string
    {
        $source = (string) ($xml->channel->title ?? $xml->title ?? '');

        if ($source !== '') {
            return $this->cleanTitle($source);
        }

        $host = parse_url($feedUrl, PHP_URL_HOST);

        return $host ? str_replace('www.', '', (string) $host) : 'Unknown source';
    }

    protected function extractLink(\SimpleXMLElement $item): string
    {
        if (isset($item->link) && trim((string) $item->link) !== '') {
            return $this->cleanLink((string) $item->link);
        }

        if (isset($item->link['href'])) {
            return $this->cleanLink((string) $item->link['href']);
        }

        if (isset($item->guid)) {
            return $this->cleanLink((string) $item->guid);
        }

        return 'unavailable';
    }

    protected function extractPublishedAt(\SimpleXMLElement $item): array
    {
        $value = (string) ($item->pubDate ?? $item->published ?? $item->updated ?? '');

        return $this->normalizeDateLabel($value);
    }

    protected function normalizeDateLabel(string $value): array
    {
        try {
            if (trim($value) === '') {
                return [
                    'label' => 'unknown',
                    'timestamp' => 0,
                ];
            }

            $date = Carbon::parse($value)->utc();

            return [
                'label' => $date->format('Y-m-d H:i') . ' UTC',
                'timestamp' => $date->timestamp,
            ];
        } catch (\Throwable) {
            return [
                'label' => 'unknown',
                'timestamp' => 0,
            ];
        }
    }

    protected function cleanTitle(string $title): string
    {
        $title = preg_replace('/<!\[CDATA\[(.*?)\]\]>/is', '$1', $title) ?? $title;
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = strip_tags($title);
        $title = trim($title);

        if ($title === '') {
            return '';
        }

        $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
        $title = iconv('UTF-8', 'UTF-8//IGNORE', $title) ?: $title;

        return Str::limit($title, 160);
    }

    protected function cleanLink(string $link): string
    {
        $link = trim($link);

        if ($link !== '' && filter_var($link, FILTER_VALIDATE_URL)) {
            return $link;
        }

        return 'unavailable';
    }
}
