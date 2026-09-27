<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoMarketService
{
    private const TOP_CACHE_PREFIX = 'crypto_top_v3_';
    private const GLOBAL_CACHE_KEY = 'crypto_global_metrics_v3';
    private const CMC_COOLDOWN_CACHE_KEY = 'crypto_coinmarketcap_cooldown_until_v1';

    private string $apiKey;
    private string $baseUrl = 'https://pro-api.coinmarketcap.com/v1';
    private string $coinGeckoBaseUrl = 'https://api.coingecko.com/api/v3';
    private int $cacheMinutes = 15; // Cache for 15 minutes to stay under limits
    private int $connectTimeoutSeconds = 2;
    private int $timeoutSeconds = 4;
    
    public function __construct()
    {
        $this->apiKey = config('services.coinmarketcap.api_key', env('COINMARKETCAP_API_KEY'));
    }
    
    /**
     * Get top cryptocurrencies with caching to respect API limits
     * Monthly limit: 10,000 calls
     * Rate limit: 30 requests/minute
     * 
     * With 15-minute cache:
     * - Max 96 calls per day (24 hours * 4 calls/hour)
     * - Max ~2,880 calls per month (96 * 30)
     * - Well under the 10,000 monthly limit
     */
    public function getTopCryptos(int $limit = 5): array
    {
        $cacheKey = self::TOP_CACHE_PREFIX . $limit;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->fetchTopCryptos($limit);

        if ($result['success'] ?? false) {
            Cache::put($cacheKey, $result['data'], now()->addMinutes($this->cacheMinutes));
        }

        return $result['data'];
    }

    public function getWidgetPayload(int $limit = 5): array
    {
        $quotes = $this->fetchTopCryptos($limit);
        $global = $this->fetchGlobalMetricsPayload();

        return [
            'success' => ! empty($quotes['data']),
            'cryptos' => $quotes['data'],
            'global' => $global['data'],
            'is_live' => ($quotes['success'] ?? false) && ($global['success'] ?? false),
            'source' => ($quotes['success'] ?? false) && ($global['success'] ?? false)
                ? (($quotes['source'] ?? null) === ($global['source'] ?? null) ? ($quotes['source'] ?? 'coinmarketcap') : 'mixed')
                : 'fallback',
            'message' => ($quotes['success'] ?? false) && ($global['success'] ?? false)
                ? null
                : 'Crypto market data is temporarily unavailable. Showing the most recent safe fallback snapshot.',
        ];
    }
    
    /**
     * Fetch from CoinMarketCap API
     */
    private function fetchTopCryptos(int $limit): array
    {
        if ($this->apiKey === '' || $this->shouldUseCoinGecko()) {
            return $this->fetchCoinGeckoTopCryptos($limit);
        }

        try {
            $response = Http::withHeaders([
                'X-CMC_PRO_API_KEY' => $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->get($this->baseUrl . '/cryptocurrency/listings/latest', [
                'limit' => $limit,
                'convert' => 'USD',
                'sort' => 'market_cap',
                'sort_dir' => 'desc',
            ]);

            if ($response->successful()) {
                $this->clearCoinMarketCapCooldown();
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $this->formatCryptoData($data['data'] ?? []),
                    'source' => 'coinmarketcap',
                ];
            }
            
            Log::warning('CoinMarketCap API error, using fallback snapshot', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->status() === 429) {
                $this->activateCoinMarketCapCooldown();
            }

            return $this->fetchCoinGeckoTopCryptos($limit);

        } catch (\Exception $e) {
            Log::warning('CoinMarketCap API exception, using fallback snapshot', [
                'message' => $e->getMessage(),
            ]);

            return $this->fetchCoinGeckoTopCryptos($limit);
        }
    }
    
    /**
     * Format API response for display
     */
    private function formatCryptoData(array $data): array
    {
        $formatted = [];
        
        foreach ($data as $crypto) {
            $quote = $crypto['quote']['USD'] ?? [];
            
            $formatted[] = [
                'id' => $crypto['id'],
                'name' => $crypto['name'],
                'symbol' => $crypto['symbol'],
                'slug' => $crypto['slug'],
                'price' => $quote['price'] ?? 0,
                'percent_change_24h' => $quote['percent_change_24h'] ?? 0,
                'percent_change_7d' => $quote['percent_change_7d'] ?? 0,
                'market_cap' => $quote['market_cap'] ?? 0,
                'volume_24h' => $quote['volume_24h'] ?? 0,
                'circulating_supply' => $crypto['circulating_supply'] ?? 0,
                'last_updated' => $crypto['last_updated'],
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Fallback data when API fails
     */
    private function getFallbackData(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'slug' => 'bitcoin',
                'price' => 43521.34,
                'percent_change_24h' => 2.34,
                'percent_change_7d' => 5.67,
                'market_cap' => 850000000000,
                'volume_24h' => 0,
                'circulating_supply' => 0,
                'last_updated' => now()->toIso8601String(),
            ],
            [
                'id' => 1027,
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'slug' => 'ethereum',
                'price' => 2284.56,
                'percent_change_24h' => 1.23,
                'percent_change_7d' => -2.45,
                'market_cap' => 274000000000,
                'volume_24h' => 0,
                'circulating_supply' => 0,
                'last_updated' => now()->toIso8601String(),
            ],
            [
                'id' => 825,
                'name' => 'Tether',
                'symbol' => 'USDT',
                'slug' => 'tether',
                'price' => 1.00,
                'percent_change_24h' => 0.01,
                'percent_change_7d' => 0.02,
                'market_cap' => 91000000000,
                'volume_24h' => 0,
                'circulating_supply' => 0,
                'last_updated' => now()->toIso8601String(),
            ],
            [
                'id' => 1839,
                'name' => 'BNB',
                'symbol' => 'BNB',
                'slug' => 'bnb',
                'price' => 312.45,
                'percent_change_24h' => -0.45,
                'percent_change_7d' => 3.21,
                'market_cap' => 48000000000,
                'volume_24h' => 0,
                'circulating_supply' => 0,
                'last_updated' => now()->toIso8601String(),
            ],
            [
                'id' => 5426,
                'name' => 'Solana',
                'symbol' => 'SOL',
                'slug' => 'solana',
                'price' => 98.76,
                'percent_change_24h' => 5.43,
                'percent_change_7d' => 12.34,
                'market_cap' => 42000000000,
                'volume_24h' => 0,
                'circulating_supply' => 0,
                'last_updated' => now()->toIso8601String(),
            ],
        ];
    }
    
    /**
     * Get global market metrics
     */
    public function getGlobalMetrics(): array
    {
        return $this->fetchGlobalMetricsPayload()['data'];
    }

    private function fetchGlobalMetricsPayload(): array
    {
        return Cache::remember(self::GLOBAL_CACHE_KEY, now()->addMinutes($this->cacheMinutes), function () {
            if ($this->apiKey === '' || $this->shouldUseCoinGecko()) {
                return $this->fetchCoinGeckoGlobalMetrics();
            }

            try {
                $response = Http::withHeaders([
                    'X-CMC_PRO_API_KEY' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                    ->connectTimeout($this->connectTimeoutSeconds)
                    ->timeout($this->timeoutSeconds)
                    ->get($this->baseUrl . '/global-metrics/quotes/latest');

                if ($response->successful()) {
                    $this->clearCoinMarketCapCooldown();
                    $data = $response->json()['data'] ?? [];
                    $quote = $data['quote']['USD'] ?? [];

                    return [
                        'success' => true,
                        'source' => 'coinmarketcap',
                        'data' => [
                            'total_market_cap' => $quote['total_market_cap'] ?? 0,
                            'total_volume_24h' => $quote['total_volume_24h'] ?? 0,
                            'btc_dominance' => $data['btc_dominance'] ?? 0,
                            'eth_dominance' => $data['eth_dominance'] ?? 0,
                            'active_cryptocurrencies' => $data['active_cryptocurrencies'] ?? 0,
                        ],
                    ];
                }

                if ($response->status() === 429) {
                    $this->activateCoinMarketCapCooldown();
                }
            } catch (\Exception $e) {
                Log::warning('CoinMarketCap global metrics exception, using fallback snapshot', [
                    'message' => $e->getMessage(),
                ]);
            }

            return $this->fetchCoinGeckoGlobalMetrics();
        });
    }

    private function fetchCoinGeckoTopCryptos(int $limit): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; GenZNewZBot/1.0; +https://genznewz.com)',
                'Accept' => 'application/json',
            ])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->get($this->coinGeckoBaseUrl . '/coins/markets', [
                    'vs_currency' => 'usd',
                    'ids' => 'bitcoin,ethereum,tether,binancecoin,solana',
                    'order' => 'market_cap_desc',
                    'per_page' => $limit,
                    'page' => 1,
                    'sparkline' => 'false',
                    'price_change_percentage' => '24h',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'source' => 'coingecko',
                    'data' => collect($response->json())
                        ->take($limit)
                        ->map(function (array $crypto): array {
                            return [
                                'id' => $crypto['id'] ?? null,
                                'name' => $crypto['name'] ?? '',
                                'symbol' => strtoupper((string) ($crypto['symbol'] ?? '')),
                                'slug' => $crypto['id'] ?? '',
                                'price' => (float) ($crypto['current_price'] ?? 0),
                                'percent_change_24h' => (float) ($crypto['price_change_percentage_24h_in_currency'] ?? $crypto['price_change_percentage_24h'] ?? 0),
                                'percent_change_7d' => 0.0,
                                'market_cap' => (float) ($crypto['market_cap'] ?? 0),
                                'volume_24h' => (float) ($crypto['total_volume'] ?? 0),
                                'circulating_supply' => (float) ($crypto['circulating_supply'] ?? 0),
                                'last_updated' => $crypto['last_updated'] ?? now()->toIso8601String(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CoinGecko market data exception, using fallback snapshot', [
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'source' => 'fallback',
            'data' => $this->getFallbackData(),
        ];
    }

    private function fetchCoinGeckoGlobalMetrics(): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; GenZNewZBot/1.0; +https://genznewz.com)',
                'Accept' => 'application/json',
            ])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->get($this->coinGeckoBaseUrl . '/global');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];

                return [
                    'success' => true,
                    'source' => 'coingecko',
                    'data' => [
                        'total_market_cap' => (float) ($data['total_market_cap']['usd'] ?? 0),
                        'total_volume_24h' => (float) ($data['total_volume']['usd'] ?? 0),
                        'btc_dominance' => (float) ($data['market_cap_percentage']['btc'] ?? 0),
                        'eth_dominance' => (float) ($data['market_cap_percentage']['eth'] ?? 0),
                        'active_cryptocurrencies' => (float) ($data['active_cryptocurrencies'] ?? 0),
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CoinGecko global metrics exception, using fallback snapshot', [
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'source' => 'fallback',
            'data' => $this->getFallbackGlobalMetrics(),
        ];
    }

    private function shouldUseCoinGecko(): bool
    {
        return (int) Cache::get(self::CMC_COOLDOWN_CACHE_KEY, 0) > now()->timestamp;
    }

    private function activateCoinMarketCapCooldown(int $minutes = 360): void
    {
        $until = now()->addMinutes($minutes);

        Cache::put(self::CMC_COOLDOWN_CACHE_KEY, $until->timestamp, $until);
    }

    private function clearCoinMarketCapCooldown(): void
    {
        Cache::forget(self::CMC_COOLDOWN_CACHE_KEY);
    }
    
    /**
     * Fallback global metrics
     */
    private function getFallbackGlobalMetrics(): array
    {
        return [
            'total_market_cap' => 1650000000000,
            'total_volume_24h' => 52000000000,
            'btc_dominance' => 51.5,
            'eth_dominance' => 16.8,
            'active_cryptocurrencies' => 8500,
        ];
    }
    
    /**
     * Format large numbers (billions, millions)
     */
    public static function formatNumber(float $number, int $decimals = 2): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, $decimals) . 'B';
        }
        if ($number >= 1000000) {
            return number_format($number / 1000000, $decimals) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, $decimals) . 'K';
        }
        return number_format($number, $decimals);
    }
    
    /**
     * Format price with appropriate decimals
     */
    public static function formatPrice(float $price): string
    {
        if ($price >= 1000) {
            return '$' . number_format($price, 2);
        }
        if ($price >= 1) {
            return '$' . number_format($price, 2);
        }
        if ($price >= 0.01) {
            return '$' . number_format($price, 4);
        }
        return '$' . number_format($price, 6);
    }
    
    /**
     * Get CSS class for price change
     */
    public static function getChangeClass(float $change): string
    {
        return $change >= 0 ? 'crypto-up' : 'crypto-down';
    }
    
    /**
     * Get change arrow/icon
     */
    public static function getChangeArrow(float $change): string
    {
        return $change >= 0 ? '▲' : '▼';
    }
    
    /**
     * Clear cache - useful for manual refresh
     */
    public function clearCache(): void
    {
        Cache::forget(self::TOP_CACHE_PREFIX . '5');
        Cache::forget(self::GLOBAL_CACHE_KEY);
        Cache::forget(self::CMC_COOLDOWN_CACHE_KEY);
    }
}
