<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchRssFeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;
    public $backoff = [10, 30];

    protected $category;
    protected array $feedUrls;

    public function __construct($category, array $feedUrls)
    {
        $this->category = $category;
        $this->feedUrls = $feedUrls;
    }

    public function handle(): void
    {
        $categoryName = $this->getCategoryName();
        $categoryHeadlines = [];
        $successfulFetches = 0;
        $failedFetches = 0;

        foreach ($this->feedUrls as $url) {
            try {
                $headlines = $this->fetchFeedHeadlines($url);
                if (!empty($headlines)) {
                    $categoryHeadlines = array_merge($categoryHeadlines, $headlines);
                    $successfulFetches++;
                } else {
                    $failedFetches++;
                }
            } catch (\Exception $e) {
                Log::warning("RSS fetch failed for {$url}: " . $e->getMessage());
                $failedFetches++;
                continue;
            }
        }

        // Sort by date (newest first)
        usort($categoryHeadlines, function($a, $b) {
            $aTime = strtotime($a['pubDate'] ?? 'now');
            $bTime = strtotime($b['pubDate'] ?? 'now');
            return $bTime - $aTime;
        });

        // Take top headlines
        $topHeadlines = array_slice($categoryHeadlines, 0, 12);

        // If no headlines fetched, use fallback
        if (empty($topHeadlines)) {
            $topHeadlines = $this->getFallbackHeadlines($categoryName);
        }

        $cacheKey = 'live_events_category_' . md5($categoryName);
        Cache::put($cacheKey, [
            'category' => $categoryName,
            'headlines' => $topHeadlines,
            'fetched_at' => now()->toIso8601String(),
            'stats' => [
                'successful' => $successfulFetches,
                'failed' => $failedFetches,
            ],
        ], 900);

        Log::info("RSS fetched for category: " . $categoryName . " (" . count($topHeadlines) . " headlines, {$successfulFetches} sources succeeded, {$failedFetches} failed)");
    }

    private function getCategoryName(): string
    {
        if (is_array($this->category)) {
            return implode(', ', $this->category);
        }
        return (string) $this->category;
    }

    /**
     * Fallback headlines when all feeds fail
     */
    protected function getFallbackHeadlines(string $category): array
    {
        $fallbacks = [
            'Breaking News' => [
                ['title' => 'Latest breaking news updates from around the world', 'link' => '#', 'pubDate' => now()->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'Stay tuned for real-time news updates', 'link' => '#', 'pubDate' => now()->subMinutes(5)->toRssString(), 'source' => 'GenZ NewZ'],
                ['title' => 'News feeds temporarily unavailable - checking sources', 'link' => '#', 'pubDate' => now()->subMinutes(10)->toRssString(), 'source' => 'GenZ NewZ'],
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

    protected function fetchFeedHeadlines(string $url): array
    {
        $headlines = [];
        
        try {
            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => true,
                    'follow_redirects' => true,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.0.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.0.36',
                    'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::debug("RSS feed returned status {$response->status()}: {$url}");
                return [];
            }

            $body = $response->body();
            
            // Try different encodings
            $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1251,Windows-1252');
            
            // Suppress XML parsing errors
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();

            if ($xml === false) {
                Log::debug("Failed to parse RSS feed: {$url}");
                return [];
            }

            // Try different RSS/Atom formats
            $items = [];
            
            if (isset($xml->channel->item)) {
                // Standard RSS 2.0
                $items = $xml->channel->item;
            } elseif (isset($xml->item)) {
                // RSS 1.0
                $items = $xml->item;
            } elseif (isset($xml->entry)) {
                // Atom
                $items = $xml->entry;
            }

            $sourceName = $this->extractSourceName($xml, $url);

            foreach ($items as $item) {
                $title = $this->cleanTitle((string) ($item->title ?? ''));
                $link = $this->extractLink($item);
                $pubDate = $this->extractPubDate($item);

                if (!empty($title) && strlen($title) > 5) {
                    $headlines[] = [
                        'title' => $title,
                        'link' => $link,
                        'pubDate' => $pubDate,
                        'source' => $sourceName,
                    ];
                }

                if (count($headlines) >= 6) {
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::debug("Error fetching feed {$url}: " . $e->getMessage());
        }

        return $headlines;
    }

    /**
     * Extract source name from XML or URL
     */
    protected function extractSourceName($xml, string $url): string
    {
        if (isset($xml->channel->title)) {
            return $this->cleanTitle((string) $xml->channel->title);
        }
        if (isset($xml->title)) {
            return $this->cleanTitle((string) $xml->title);
        }
        
        $host = parse_url($url, PHP_URL_HOST);
        return str_replace('www.', '', $host ?? 'Unknown');
    }

    /**
     * Extract link from item (handles RSS and Atom)
     */
    protected function extractLink($item): string
    {
        // Try standard link
        if (isset($item->link) && !empty((string) $item->link)) {
            return (string) $item->link;
        }
        
        // Try Atom href attribute
        if (isset($item->link['href'])) {
            return (string) $item->link['href'];
        }
        
        // Try guid
        if (isset($item->guid) && !empty((string) $item->guid)) {
            $guid = (string) $item->guid;
            // Only use guid as link if it looks like a URL
            if (filter_var($guid, FILTER_VALIDATE_URL)) {
                return $guid;
            }
        }
        
        return '#';
    }

    /**
     * Extract publication date from item
     */
    protected function extractPubDate($item): string
    {
        $date = '';
        
        if (isset($item->pubDate)) {
            $date = (string) $item->pubDate;
        } elseif (isset($item->published)) {
            $date = (string) $item->published;
        } elseif (isset($item->updated)) {
            $date = (string) $item->updated;
        } elseif (isset($item->date)) {
            $date = (string) $item->date;
        }
        
        // Validate date
        if (!empty($date) && strtotime($date) !== false) {
            return $date;
        }
        
        return now()->toRssString();
    }

    /**
     * Clean and format title
     */
    protected function cleanTitle(string $title): string
    {
        // Remove CDATA wrapper
        $title = preg_replace('/<!\[CDATA\[(.*?)\]\]>/is', '$1', $title);
        
        // Decode HTML entities
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Strip HTML tags
        $title = strip_tags($title);
        
        // Trim whitespace
        $title = trim($title);
        
        // Truncate if too long
        if (strlen($title) > 150) {
            $title = substr($title, 0, 150) . '...';
        }
        
        return $title;
    }
}
