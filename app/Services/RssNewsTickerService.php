<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RssNewsTickerService
{
    /**
     * RSS Feeds organized by country
     * Using reliable international news sources
     */
    protected array $feeds = [
        'Russia' => [
            'https://ria.ru/export/rss2/archive/index.xml',
            'https://lenta.ru/rss/news',
            'https://tass.com/rss/v2.xml',
        ],
        'United States' => [
            'https://feeds.foxnews.com/foxnews/latest',
            'https://abcnews.go.com/abcnews/topstories',
        ],
        'China' => [
            'https://www.chinanews.com.cn/rss/scroll-news.xml',
        ],
        'India' => [
            'https://timesofindia.indiatimes.com/rssfeeds/-2128936835.cms',
        ],
        'Brazil' => [
            'https://g1.globo.com/rss/g1/',
        ],
    ];

    /**
     * Get cached headlines from all countries
     * Cache: 15 minutes for a good balance of freshness and performance
     */
    public function getHeadlines(): array
    {
        return Cache::remember('rss_ticker_headlines_v2', 900, function () {
            $headlines = [];
            
            foreach ($this->feeds as $country => $urls) {
                $countryHeadlines = $this->fetchCountryHeadlines($country, $urls);
                if (!empty($countryHeadlines)) {
                    $headlines[$country] = $countryHeadlines;
                }
            }
            
            // If all feeds failed, use fallback data
            if (empty($headlines)) {
                $headlines = $this->getAllFallbackHeadlines();
            }
            
            return $headlines;
        });
    }

    /**
     * Fetch headlines for a specific country
     */
    protected function fetchCountryHeadlines(string $country, array $urls): array
    {
        $headlines = [];
        $maxPerCountry = 10;
        
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(8)
                    ->withOptions([
                        'verify' => true,
                        'follow_redirects' => true,
                    ])
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.0.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.0.36',
                        'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                    ])
                    ->get($url);
                
                if ($response->successful()) {
                    $body = $response->body();
                    
                    // Handle encoding
                    $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1251,Windows-1252');
                    
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($body);
                    libxml_clear_errors();
                    
                    if ($xml) {
                        $items = $xml->channel->item ?? $xml->item ?? $xml->entry ?? [];
                        
                        foreach ($items as $item) {
                            if (count($headlines) >= $maxPerCountry) {
                                break 2;
                            }
                            
                            $title = $this->cleanTitle((string) ($item->title ?? ''));
                            $link = $this->extractLink($item);
                            
                            if (!empty($title) && strlen($title) > 10) {
                                $headlines[] = [
                                    'title' => $title,
                                    'link' => $link,
                                    'country' => $country,
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::debug("RSS ticker fetch failed for {$country}: {$url} - " . $e->getMessage());
            }
        }
        
        // Fallback if all feeds for this country failed
        if (empty($headlines)) {
            $headlines = $this->getFallbackHeadlines($country);
        }
        
        return $headlines;
    }

    /**
     * Extract link from item
     */
    protected function extractLink($item): string
    {
        if (isset($item->link) && !empty((string) $item->link)) {
            return (string) $item->link;
        }
        
        if (isset($item->link['href'])) {
            return (string) $item->link['href'];
        }
        
        if (isset($item->guid)) {
            $guid = (string) $item->guid;
            if (filter_var($guid, FILTER_VALIDATE_URL)) {
                return $guid;
            }
        }
        
        return '#';
    }

    /**
     * Fallback headlines when RSS feeds fail for a specific country
     */
    protected function getFallbackHeadlines(string $country): array
    {
        $fallbacks = [
            'Russia' => [
                ['title' => 'Latest updates from Russian news agencies', 'link' => 'https://ria.ru', 'country' => 'Russia'],
                ['title' => 'Breaking news from Moscow', 'link' => 'https://tass.com', 'country' => 'Russia'],
                ['title' => 'International relations updates', 'link' => 'https://lenta.ru', 'country' => 'Russia'],
                ['title' => 'Russian economic developments', 'link' => '#', 'country' => 'Russia'],
                ['title' => 'Technology and innovation in Russia', 'link' => '#', 'country' => 'Russia'],
            ],
            'United States' => [
                ['title' => 'Latest US news headlines', 'link' => 'https://foxnews.com', 'country' => 'United States'],
                ['title' => 'Breaking news from Washington', 'link' => 'https://abcnews.go.com', 'country' => 'United States'],
                ['title' => 'US political updates', 'link' => '#', 'country' => 'United States'],
                ['title' => 'American economic news', 'link' => '#', 'country' => 'United States'],
                ['title' => 'Technology trends in the US', 'link' => '#', 'country' => 'United States'],
            ],
            'China' => [
                ['title' => 'Latest news from China', 'link' => 'https://chinanews.com', 'country' => 'China'],
                ['title' => 'Breaking updates from Beijing', 'link' => '#', 'country' => 'China'],
                ['title' => 'China business and economy news', 'link' => '#', 'country' => 'China'],
                ['title' => 'Technology developments in China', 'link' => '#', 'country' => 'China'],
                ['title' => 'International trade updates', 'link' => '#', 'country' => 'China'],
            ],
            'India' => [
                ['title' => 'Latest headlines from India', 'link' => 'https://timesofindia.indiatimes.com', 'country' => 'India'],
                ['title' => 'Breaking news from New Delhi', 'link' => '#', 'country' => 'India'],
                ['title' => 'India international relations', 'link' => '#', 'country' => 'India'],
                ['title' => 'Indian economy and business', 'link' => '#', 'country' => 'India'],
                ['title' => 'Technology sector in India', 'link' => '#', 'country' => 'India'],
            ],
            'Brazil' => [
                ['title' => 'Últimas notícias do Brasil', 'link' => 'https://g1.globo.com', 'country' => 'Brazil'],
                ['title' => 'Atualizações de última hora de Brasília', 'link' => '#', 'country' => 'Brazil'],
                ['title' => 'Notícias de negócios e economia do Brasil', 'link' => '#', 'country' => 'Brazil'],
                ['title' => 'Esportes e entretenimento brasileiro', 'link' => '#', 'country' => 'Brazil'],
                ['title' => 'Tecnologia e inovação no Brasil', 'link' => '#', 'country' => 'Brazil'],
            ],
        ];
        
        return $fallbacks[$country] ?? [
            ['title' => "Latest news from {$country}", 'link' => '#', 'country' => $country],
        ];
    }

    /**
     * Get fallback headlines for all countries when everything fails
     */
    protected function getAllFallbackHeadlines(): array
    {
        $allFallbacks = [];
        foreach (array_keys($this->feeds) as $country) {
            $allFallbacks[$country] = $this->getFallbackHeadlines($country);
        }
        return $allFallbacks;
    }

    /**
     * Clean and format title
     */
    protected function cleanTitle(string $title): string
    {
        // Remove CDATA tags if present
        $title = preg_replace('/<!\[CDATA\[(.*?)\]\]>/is', '$1', $title);
        
        // Decode HTML entities
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Strip HTML tags
        $title = strip_tags($title);
        
        // Trim whitespace
        $title = trim($title);
        
        // Ensure valid UTF-8 encoding
        $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
        
        // Remove any invalid UTF-8 sequences
        $title = iconv('UTF-8', 'UTF-8//IGNORE', $title);
        
        // Limit length
        if (strlen($title) > 120) {
            $title = substr($title, 0, 120) . '...';
        }
        
        return $title;
    }

    /**
     * Get all countries list
     */
    public function getCountries(): array
    {
        return array_keys($this->feeds);
    }
    
    /**
     * Force refresh headlines
     */
    public function refresh(): array
    {
        Cache::forget('rss_ticker_headlines_v2');
        return $this->getHeadlines();
    }
}
