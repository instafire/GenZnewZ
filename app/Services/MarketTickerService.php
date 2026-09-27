<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketTickerService
{
    private string $cryptoApiKey;
    private string $stockApiKey;
    
    public function __construct()
    {
        $this->cryptoApiKey = config('services.coinmarketcap.api_key', env('COINMARKETCAP_API_KEY', ''));
        $this->stockApiKey = config('services.stock.api_key', env('MASSIVE_API_KEY', ''));
    }
    
    /**
     * Get live market snapshot for all sectors
     * Cache: 15 minutes to respect CoinMarketCap free plan (10,000 calls/month)
     * Max calls: ~2,880/month (well under 10,000 limit)
     */
    public function getMarketSnapshot(): array
    {
        return Cache::remember('market_snapshot', 900, function () {
            return [
                'crypto' => $this->getCryptoSentiment(),
                'ai_sector' => $this->getAISectorSentiment(),
                'tech' => $this->getTechSentiment(),
                'finance' => $this->getFinanceSentiment(),
                'energy' => $this->getEnergySentiment(),
                'commodities' => $this->getCommoditiesSentiment(),
            ];
        });
    }
    
    /**
     * Get Crypto market sentiment based on BTC and ETH performance
     */
    private function getCryptoSentiment(): array
    {
        try {
            $response = Http::withHeaders([
                'X-CMC_PRO_API_KEY' => $this->cryptoApiKey,
                'Accept' => 'application/json',
            ])->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest', [
                'symbol' => 'BTC,ETH',
                'convert' => 'USD',
            ]);
            
            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                $btcChange = $data['BTC']['quote']['USD']['percent_change_24h'] ?? 0;
                $ethChange = $data['ETH']['quote']['USD']['percent_change_24h'] ?? 0;
                $avgChange = ($btcChange + $ethChange) / 2;
                
                return [
                    'label' => 'Crypto',
                    'value' => $this->determineSentiment($avgChange),
                    'change' => round($avgChange, 2),
                    'trend' => $this->getTrendIcon($avgChange),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Crypto sentiment error: ' . $e->getMessage());
        }
        
        return $this->getFallbackSentiment('Crypto');
    }
    
    /**
     * Get AI Sector sentiment based on major AI stocks
     */
    private function getAISectorSentiment(): array
    {
        return $this->buildSectorSentiment('AI Sector', ['MSFT', 'GOOGL', 'TSLA']);
    }
    
    /**
     * Get Tech sector sentiment
     */
    private function getTechSentiment(): array
    {
        return $this->buildSectorSentiment('Tech', ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA']);
    }
    
    /**
     * Get Finance sector sentiment
     */
    private function getFinanceSentiment(): array
    {
        return $this->getUnavailableSentiment('Finance');
    }
    
    /**
     * Get Energy sector sentiment
     */
    private function getEnergySentiment(): array
    {
        return $this->getUnavailableSentiment('Energy');
    }
    
    /**
     * Get Commodities sentiment
     */
    private function getCommoditiesSentiment(): array
    {
        return $this->getUnavailableSentiment('Commodities');
    }
    
    /**
     * Get stock changes from API or cache
     */
    private function getStockChanges(array $symbols): array
    {
        $marketData = app(StockMarketService::class)->getMarketData();
        $quotes = array_merge($marketData['indices'] ?? [], $marketData['stocks'] ?? []);
        $quoteMap = [];

        foreach ($quotes as $quote) {
            $quoteMap[$quote['symbol']] = (float) ($quote['change_percent'] ?? 0);
        }

        $changes = [];
        foreach ($symbols as $symbol) {
            if (array_key_exists($symbol, $quoteMap)) {
                $changes[] = $quoteMap[$symbol];
            }
        }

        return $changes;
    }
    
    /**
     * Determine sentiment based on percentage change
     */
    private function determineSentiment(float $change): string
    {
        if ($change >= 2) {
            return 'Very Bullish';
        } elseif ($change >= 0.5) {
            return 'Bullish';
        } elseif ($change > -0.5) {
            return 'Neutral';
        } elseif ($change > -2) {
            return 'Bearish';
        } else {
            return 'Very Bearish';
        }
    }
    
    /**
     * Get trend icon/emoji
     */
    private function getTrendIcon(float $change): string
    {
        if ($change >= 2) {
            return '🚀';
        } elseif ($change >= 0.5) {
            return '📈';
        } elseif ($change > -0.5) {
            return '➡️';
        } elseif ($change > -2) {
            return '📉';
        } else {
            return '🔻';
        }
    }
    
    private function buildSectorSentiment(string $label, array $symbols): array
    {
        $changes = $this->getStockChanges($symbols);

        if (empty($changes)) {
            return $this->getUnavailableSentiment($label);
        }

        $avgChange = array_sum($changes) / count($changes);

        return [
            'label' => $label,
            'value' => $this->determineSentiment($avgChange),
            'change' => round($avgChange, 2),
            'trend' => $this->getTrendIcon($avgChange),
        ];
    }

    private function getUnavailableSentiment(string $label): array
    {
        return [
            'label' => $label,
            'value' => 'Unavailable',
            'change' => 0,
            'trend' => '•',
        ];
    }

    private function getFallbackSentiment(string $label): array
    {
        $cachedSnapshot = Cache::get('market_snapshot');

        if (is_array($cachedSnapshot)) {
            foreach ($cachedSnapshot as $sentiment) {
                if (is_array($sentiment) && ($sentiment['label'] ?? null) === $label) {
                    return $sentiment;
                }
            }
        }

        return $this->getUnavailableSentiment($label);
    }
    
    /**
     * Get market news tickers for live updates
     */
    public function getMarketTickers(): array
    {
        $tickers = [];

        $cryptos = app(CryptoMarketService::class)->getTopCryptos(2);
        foreach ($cryptos as $crypto) {
            $change = (float) ($crypto['percent_change_24h'] ?? 0);
            $tickers[] = sprintf(
                '%s %s%.2f%% over 24h',
                $crypto['symbol'],
                $change >= 0 ? '+' : '',
                $change
            );
        }

        $stockData = app(StockMarketService::class)->getMarketData();
        if (!empty($stockData['indices'])) {
            foreach (array_slice($stockData['indices'], 0, 2) as $index) {
                $change = (float) ($index['change_percent'] ?? 0);
                $tickers[] = sprintf(
                    '%s %s%.2f%%',
                    $index['name'],
                    $change >= 0 ? '+' : '',
                    $change
                );
            }
        } else {
            $tickers[] = 'Live US stock data is temporarily unavailable.';
        }

        return array_slice($tickers, 0, 8);
    }
}
