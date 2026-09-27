<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;

class StockMarketService
{
    private const MARKET_DATA_CACHE_KEY = 'stock_market_data_v4';
    private const LAST_SUCCESSFUL_CACHE_KEY = 'stock_market_last_successful_v4';

    private string $apiKey;
    private string $baseUrl = 'https://api.massive.app/v1';
    private string $yahooChartUrl = 'https://query2.finance.yahoo.com/v8/finance/chart';
    private int $cacheMinutes = 20; // Cache for 20 minutes to stay under free plan limits
    private string $lastSuccessfulCacheKey = self::LAST_SUCCESSFUL_CACHE_KEY;
    private int $connectTimeoutSeconds = 2;
    private int $timeoutSeconds = 4;
    
    // Major stock indices and popular stocks
    private array $defaultSymbols = [
        'indices' => ['SPY', 'QQQ', 'DIA', 'IWM'],  // S&P 500, NASDAQ, Dow, Russell
        'stocks' => ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA', 'META', 'NVDA', 'NFLX']
    ];
    
    public function __construct()
    {
        $this->apiKey = config('services.massive.api_key', env('MASSIVE_API_KEY'));
    }
    
    /**
     * Get stock market data with caching
     * Free Stocks Basic plan limits:
     * - Limited requests per day (typically 100-500 on free tier)
     * - 20-minute cache ensures we stay well under limits
     * 
     * With 20-minute cache:
     * - Max 72 calls per day (24 hours * 3 calls/hour)
     * - Max ~2,160 calls per month (72 * 30)
     * - Well within free tier limits
     */
    public function getMarketData(): array
    {
        return Cache::remember(self::MARKET_DATA_CACHE_KEY, now()->addMinutes($this->cacheMinutes), function () {
            return $this->fetchMarketData();
        });
    }
    
    /**
     * Fetch from Massive API
     */
    private function fetchMarketData(): array
    {
        try {
            $symbolsForStocks = array_slice($this->defaultSymbols['stocks'], 0, 5);

            if (! empty($this->apiKey)) {
                $indices = $this->fetchQuotes($this->defaultSymbols['indices']);
                $stocks = $this->fetchQuotes($symbolsForStocks);

                if (! empty($indices) || ! empty($stocks)) {
                    return $this->buildMarketPayload($indices, $stocks, 'massive', true, null);
                }
            }

            $yahooIndices = $this->fetchYahooChartQuotes($this->defaultSymbols['indices']);
            $yahooStocks = $this->fetchYahooChartQuotes($symbolsForStocks);

            if ($yahooIndices !== [] || $yahooStocks !== []) {
                return $this->buildMarketPayload(
                    $yahooIndices,
                    $yahooStocks,
                    'yahoo',
                    false,
                    'Showing a market snapshot via Yahoo Finance.'
                );
            }

            $stooqIndices = $this->fetchStooqQuotes($this->defaultSymbols['indices']);
            $stooqStocks = $this->fetchStooqQuotes($symbolsForStocks);

            if ($stooqIndices !== [] || $stooqStocks !== []) {
                return $this->buildMarketPayload(
                    $stooqIndices,
                    $stooqStocks,
                    'stooq',
                    false,
                    'Showing a delayed market snapshot.'
                );
            }

            Log::info('Stock providers returned no data, using last successful snapshot or unavailable state');
            return $this->getLastSuccessfulOrUnavailable('Live stock data is temporarily unavailable.');
            
        } catch (\Exception $e) {
            Log::warning('Stock market provider exception, using fallback snapshot', [
                'message' => $e->getMessage(),
            ]);
            
            return $this->getLastSuccessfulOrUnavailable('Live stock data is temporarily unavailable.');
        }
    }
    
    /**
     * Fetch quotes from Massive API
     * Massive API endpoint: /stocks/prices (based on their documentation)
     */
    private function fetchQuotes(array $symbols): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $quotesBySymbol = [];
        $joinedSymbols = implode(',', $symbols);
        $batchRequests = [
            ['/stocks/prices', ['tickers' => $joinedSymbols]],
            ['/stocks/prices', ['symbols' => $joinedSymbols]],
            ['/stocks/quotes', ['tickers' => $joinedSymbols]],
            ['/stocks/quotes', ['symbols' => $joinedSymbols]],
        ];

        foreach ($batchRequests as [$endpoint, $query]) {
            $batchQuotes = $this->fetchQuoteBatch($endpoint, $query, $symbols);

            foreach ($batchQuotes as $quote) {
                $quotesBySymbol[$quote['symbol']] = $quote;
            }

            if (count($quotesBySymbol) === count($symbols)) {
                return array_values($quotesBySymbol);
            }
        }

        $remainingSymbols = array_values(array_diff($symbols, array_keys($quotesBySymbol)));

        if ($remainingSymbols !== []) {
            $responses = Http::pool(function (Pool $pool) use ($remainingSymbols) {
                $requests = [];

                foreach ($remainingSymbols as $symbol) {
                    $requests[$symbol] = $pool
                        ->as($symbol)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Accept' => 'application/json',
                        ])
                        ->connectTimeout($this->connectTimeoutSeconds)
                        ->timeout($this->timeoutSeconds)
                        ->get($this->baseUrl . '/stocks/' . $symbol . '/price');
                }

                return $requests;
            });

            foreach ($remainingSymbols as $symbol) {
                $response = $responses[$symbol] ?? null;

                if (! $response instanceof Response) {
                    continue;
                }

                $quote = $this->normalizeQuoteRow($response->json(), $symbols, $symbol);

                if ($response->successful() && $quote) {
                    $quotesBySymbol[$quote['symbol']] = $quote;
                }
            }
        }

        if ($quotesBySymbol === []) {
            Log::warning('Massive API returned no data, using fallback');
        }

        return array_values($quotesBySymbol);
    }

    private function fetchYahooChartQuotes(array $symbols): array
    {
        if ($symbols === []) {
            return [];
        }

        $responses = Http::pool(function (Pool $pool) use ($symbols) {
            $requests = [];

            foreach ($symbols as $symbol) {
                $requests[$symbol] = $pool
                    ->as($symbol)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; GenZNewZBot/1.0; +https://genznewz.com)',
                        'Accept' => 'application/json',
                    ])
                    ->connectTimeout($this->connectTimeoutSeconds)
                    ->timeout($this->timeoutSeconds)
                    ->get($this->yahooChartUrl . '/' . $symbol, [
                        'range' => '5d',
                        'interval' => '1d',
                        'includePrePost' => 'false',
                        'events' => 'div,splits',
                    ]);
            }

            return $requests;
        });

        $quotes = [];

        foreach ($symbols as $symbol) {
            $response = $responses[$symbol] ?? null;

            if (! $response instanceof Response || ! $response->successful()) {
                continue;
            }

            $quote = $this->extractYahooChartQuote($symbol, $response->json());

            if ($quote) {
                $quotes[$quote['symbol']] = $quote;
            }
        }

        return array_values($quotes);
    }

    private function fetchStooqQuotes(array $symbols): array
    {
        $quotes = [];
        $endDate = now('America/New_York')->format('Ymd');
        $startDate = now('America/New_York')->subDays(7)->format('Ymd');

        foreach ($symbols as $symbol) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; GenZNewZBot/1.0; +https://genznewz.com)',
                    'Accept' => 'text/csv, text/plain, */*',
                ])
                    ->connectTimeout($this->connectTimeoutSeconds)
                    ->timeout($this->timeoutSeconds)
                    ->get('https://stooq.com/q/d/l/', [
                        's' => strtolower($symbol) . '.us',
                        'd1' => $startDate,
                        'd2' => $endDate,
                        'i' => 'd',
                    ]);
            } catch (\Throwable $e) {
                continue;
            }

            if (! ($response instanceof Response) || ! $response->successful()) {
                continue;
            }

            $quote = $this->parseStooqCsv($symbol, (string) $response->body());

            if ($quote) {
                $quotes[$quote['symbol']] = $quote;
            }
        }

        return array_values($quotes);
    }

    private function fetchQuoteBatch(string $endpoint, array $query, array $symbols): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->get($this->baseUrl . $endpoint, $query);
        } catch (\Throwable $e) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $rows = $this->extractQuoteRows($response->json());
        $quotes = [];

        foreach ($rows as $rowKey => $row) {
            $quote = $this->normalizeQuoteRow($row, $symbols, is_string($rowKey) ? $rowKey : null);

            if ($quote) {
                $quotes[$quote['symbol']] = $quote;
            }
        }

        return array_values($quotes);
    }

    private function extractQuoteRows(mixed $payload): array
    {
        if (! is_array($payload) || $payload === []) {
            return [];
        }

        foreach (['data', 'results', 'quotes', 'tickers', 'items'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        if ($this->looksLikeQuoteRow($payload)) {
            return [$payload];
        }

        return $payload;
    }

    private function normalizeQuoteRow(mixed $row, array $symbols, ?string $fallbackSymbol = null): ?array
    {
        if (! is_array($row)) {
            return null;
        }

        $symbol = strtoupper(trim((string) ($row['symbol'] ?? $row['ticker'] ?? $row['code'] ?? $fallbackSymbol ?? '')));

        if ($symbol === '' || ! in_array($symbol, $symbols, true)) {
            return null;
        }

        $price = $this->extractNumericValue($row, ['price', 'last', 'last_price', 'close', 'value']);

        if ($price === null) {
            return null;
        }

        $change = $this->extractNumericValue($row, ['change', 'todaysChange', 'net_change']) ?? 0.0;
        $changePercent = $this->extractNumericValue($row, ['changePercent', 'change_percent', 'percent_change', 'todaysChangePerc']) ?? 0.0;

        return [
            'symbol' => $symbol,
            'price' => $price,
            'change' => $change,
            'change_percent' => $changePercent,
        ];
    }

    private function looksLikeQuoteRow(array $row): bool
    {
        return isset($row['symbol']) || isset($row['ticker']) || isset($row['price']) || isset($row['last']);
    }

    private function extractNumericValue(array $row, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];

            if (is_numeric($value)) {
                return (float) $value;
            }

            if (is_string($value)) {
                $normalized = str_replace([',', '%', '$'], '', trim($value));

                if (is_numeric($normalized)) {
                    return (float) $normalized;
                }
            }
        }

        return null;
    }

    private function parseStooqCsv(string $symbol, string $csv): ?array
    {
        $lines = preg_split('/\r\n|\n|\r/', trim($csv));

        if (! is_array($lines) || count($lines) < 2) {
            return null;
        }

        $rows = array_values(array_filter(array_map('trim', $lines)));

        if (count($rows) < 2) {
            return null;
        }

        $latest = str_getcsv($rows[count($rows) - 1]);
        $previous = count($rows) > 2 ? str_getcsv($rows[count($rows) - 2]) : null;

        $close = isset($latest[4]) && is_numeric($latest[4]) ? (float) $latest[4] : null;
        $previousClose = $previous && isset($previous[4]) && is_numeric($previous[4])
            ? (float) $previous[4]
            : (isset($latest[1]) && is_numeric($latest[1]) ? (float) $latest[1] : null);

        if ($close === null || $previousClose === null || $previousClose == 0.0) {
            return null;
        }

        $change = $close - $previousClose;

        return [
            'symbol' => $symbol,
            'price' => $close,
            'change' => $change,
            'change_percent' => ($change / $previousClose) * 100,
        ];
    }

    private function extractYahooChartQuote(string $symbol, mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $meta = $payload['chart']['result'][0]['meta'] ?? null;

        if (! is_array($meta)) {
            return null;
        }

        $price = isset($meta['regularMarketPrice']) && is_numeric($meta['regularMarketPrice'])
            ? (float) $meta['regularMarketPrice']
            : null;
        $previousClose = isset($meta['chartPreviousClose']) && is_numeric($meta['chartPreviousClose'])
            ? (float) $meta['chartPreviousClose']
            : null;

        if ($price === null || $previousClose === null || $previousClose == 0.0) {
            return null;
        }

        $change = $price - $previousClose;

        return [
            'symbol' => strtoupper($symbol),
            'price' => $price,
            'change' => $change,
            'change_percent' => ($change / $previousClose) * 100,
        ];
    }

    private function buildMarketPayload(array $indices, array $stocks, string $source, bool $isLive, ?string $message): array
    {
        $payload = [
            'indices' => $this->formatIndices($indices),
            'stocks' => $this->formatStocks($stocks),
            'last_updated' => now()->toIso8601String(),
            'is_live' => $isLive,
            'source' => $source,
            'message' => $message,
        ];

        Cache::put($this->lastSuccessfulCacheKey, $payload, now()->addDay());

        return $payload;
    }
    
    /**
     * Format indices data
     */
    private function formatIndices(array $data): array
    {
        $indices = [];
        $indexNames = [
            'SPY' => 'S&P 500',
            'QQQ' => 'NASDAQ',
            'DIA' => 'Dow Jones',
            'IWM' => 'Russell 2000',
        ];
        
        foreach ($data as $quote) {
            $symbol = $quote['symbol'] ?? '';
            if (isset($indexNames[$symbol])) {
                $indices[] = [
                    'symbol' => $symbol,
                    'name' => $indexNames[$symbol],
                    'price' => $quote['price'] ?? 0,
                    'change' => $quote['change'] ?? 0,
                    'change_percent' => $quote['change_percent'] ?? 0,
                ];
            }
        }
        
        return $indices;
    }
    
    /**
     * Format stocks data
     */
    private function formatStocks(array $data): array
    {
        $stocks = [];
        
        foreach ($data as $quote) {
            $stocks[] = [
                'symbol' => $quote['symbol'] ?? '',
                'name' => $this->getCompanyName($quote['symbol'] ?? ''),
                'price' => $quote['price'] ?? 0,
                'change' => $quote['change'] ?? 0,
                'change_percent' => $quote['change_percent'] ?? 0,
            ];
        }
        
        return $stocks;
    }
    
    /**
     * Get company name from symbol
     */
    private function getCompanyName(string $symbol): string
    {
        $names = [
            'AAPL' => 'Apple Inc.',
            'MSFT' => 'Microsoft',
            'GOOGL' => 'Alphabet Inc.',
            'AMZN' => 'Amazon.com',
            'TSLA' => 'Tesla Inc.',
            'META' => 'Meta Platforms',
            'NVDA' => 'NVIDIA Corp.',
            'NFLX' => 'Netflix Inc.',
            'SPY' => 'S&P 500 ETF',
            'QQQ' => 'NASDAQ-100 ETF',
            'DIA' => 'Dow Jones ETF',
            'IWM' => 'Russell 2000 ETF',
        ];
        
        return $names[$symbol] ?? $symbol;
    }
    
    /**
     * Get market status
     */
    public function getMarketStatus(): string
    {
        $now = now('America/New_York');
        $day = $now->dayOfWeek;
        $minutes = ($now->hour * 60) + $now->minute;
        
        // US Stock Market Hours: 9:30 AM - 4:00 PM ET, Mon-Fri
        if ($day >= 1 && $day <= 5) { // Monday to Friday
            if ($minutes >= 570 && $minutes < 960) {
                return 'open';
            }
        }
        
        return 'closed';
    }
    
    /**
     * Fallback data when API fails
     */
    private function getLastSuccessfulOrUnavailable(string $message): array
    {
        $lastSuccessful = Cache::get($this->lastSuccessfulCacheKey);

        if (is_array($lastSuccessful) && (!empty($lastSuccessful['indices']) || !empty($lastSuccessful['stocks']))) {
            $lastSuccessful['is_live'] = false;
            $lastSuccessful['source'] = 'stale';
            $lastSuccessful['message'] = 'Showing the last successful stock market snapshot.';

            return $lastSuccessful;
        }

        return $this->getUnavailableData($message);
    }

    private function getUnavailableData(string $message): array
    {
        return [
            'indices' => [],
            'stocks' => [],
            'last_updated' => null,
            'is_live' => false,
            'source' => 'unavailable',
            'message' => $message,
        ];
    }
    
    /**
     * Format price
     */
    public static function formatPrice(float $price): string
    {
        return '$' . number_format($price, 2);
    }
    
    /**
     * Format change with sign
     */
    public static function formatChange(float $change): string
    {
        $sign = $change >= 0 ? '+' : '';
        return $sign . number_format($change, 2);
    }
    
    /**
     * Get CSS class for price change
     */
    public static function getChangeClass(float $change): string
    {
        return $change >= 0 ? 'stock-up' : 'stock-down';
    }
    
    /**
     * Get change arrow
     */
    public static function getChangeArrow(float $change): string
    {
        return $change >= 0 ? '▲' : '▼';
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::MARKET_DATA_CACHE_KEY);
        Cache::forget(self::LAST_SUCCESSFUL_CACHE_KEY);
    }
}
