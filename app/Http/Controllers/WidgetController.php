<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Services\CryptoMarketService;
use App\Services\StockMarketService;

class WidgetController extends Controller
{
    /**
     * Get crypto market data via AJAX
     */
    public function cryptoMarket(): JsonResponse
    {
        $data = Cache::remember('ajax_widget_crypto_v3', now()->addMinutes(30), function () {
            try {
                $service = new CryptoMarketService();
                return $service->getWidgetPayload(5);
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });

        return response()->json($data);
    }

    /**
     * Get stock market data via AJAX
     */
    public function stockMarket(): JsonResponse
    {
        $data = Cache::remember('ajax_widget_stock_v4', now()->addMinutes(30), function () {
            try {
                $service = new StockMarketService();
                $marketData = $service->getMarketData();

                return [
                    'success' => !empty($marketData['indices']) || !empty($marketData['stocks']),
                    'data' => $marketData,
                    'status' => $service->getMarketStatus(),
                ];
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });

        return response()->json($data);
    }

    /**
     * Render crypto widget HTML
     */
    public function cryptoWidgetHtml(): Response
    {
        $data = Cache::remember('widget_crypto_data_v3', now()->addMinutes(30), function () {
            try {
                $service = new CryptoMarketService();
                return $service->getWidgetPayload(5);
            } catch (\Exception $e) {
                return ['success' => false];
            }
        });

        if (!$data['success']) {
            return response('', 204)->header('Cache-Control', 'no-store');
        }

        // Render inline HTML instead of using view
        $html = $this->renderCryptoWidget($data);
        return response($html)->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=30');
    }

    /**
     * Render stock widget HTML
     */
    public function stockWidgetHtml(): Response
    {
        $data = Cache::remember('widget_stock_data_v4', now()->addMinutes(30), function () {
            try {
                $service = new StockMarketService();
                return [
                    'success' => true,
                    'data' => $service->getMarketData(),
                    'status' => $service->getMarketStatus(),
                ];
            } catch (\Exception $e) {
                return ['success' => false];
            }
        });

        if (!$data['success']) {
            return response('', 204)->header('Cache-Control', 'no-store');
        }

        // Render inline HTML instead of using view
        $html = $this->renderStockWidget($data);
        return response($html)->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=30');
    }

    /**
     * Render crypto widget inline
     */
    private function renderCryptoWidget(array $data): string
    {
        $cryptos = $data['cryptos'];
        $global = $data['global'];
        $isLive = (bool) ($data['is_live'] ?? false);
        $badgeLabel = $isLive ? 'LIVE' : 'DELAYED';
        $source = (string) ($data['source'] ?? 'fallback');
        $sourceLabel = match ($source) {
            'coinmarketcap' => 'Data by CoinMarketCap',
            'coingecko' => 'Data by CoinGecko',
            'mixed' => 'Live market data',
            default => 'Fallback market snapshot',
        };
        $statusMessage = $data['message'] ?? null;
        
        if (empty($cryptos)) {
            return '';
        }

        $formatNumber = function($num) {
            if ($num >= 1000000000) {
                return number_format($num / 1000000000, 2) . 'B';
            } elseif ($num >= 1000000) {
                return number_format($num / 1000000, 2) . 'M';
            } elseif ($num >= 1000) {
                return number_format($num / 1000, 2) . 'K';
            }
            return number_format($num);
        };

        $html = '<div class="crypto-widget">';
        $html .= '<div class="crypto-widget-header">';
        $html .= '<h4 class="crypto-widget-title"><span class="crypto-icon">₿</span>Crypto Markets</h4>';
        $html .= '<span class="crypto-live-badge">' . e($badgeLabel) . '</span>';
        $html .= '</div>';

        if ($statusMessage) {
            $html .= '<div class="crypto-empty-state">' . e($statusMessage) . '</div>';
        }
        
        $html .= '<div class="crypto-global-metrics">';
        $html .= '<div class="crypto-metric-item"><span class="crypto-metric-label">Total Cap</span><span class="crypto-metric-value">$' . $formatNumber($global['total_market_cap'] ?? 0) . '</span></div>';
        $html .= '<div class="crypto-metric-item"><span class="crypto-metric-label">24h Vol</span><span class="crypto-metric-value">$' . $formatNumber($global['total_volume_24h'] ?? 0) . '</span></div>';
        $html .= '<div class="crypto-metric-item"><span class="crypto-metric-label">BTC Dom</span><span class="crypto-metric-value">' . number_format($global['btc_dominance'] ?? 0, 1) . '%</span></div>';
        $html .= '</div>';
        
        $html .= '<div class="crypto-list">';
        foreach ($cryptos as $i => $crypto) {
            $changeClass = ($crypto['percent_change_24h'] ?? 0) >= 0 ? 'crypto-up' : 'crypto-down';
            $arrow = ($crypto['percent_change_24h'] ?? 0) >= 0 ? '▲' : '▼';
            $html .= '<div class="crypto-item">';
            $html .= '<div class="crypto-rank-info"><span class="crypto-rank">' . ($i + 1) . '</span><div class="crypto-info"><span class="crypto-symbol">' . e((string) ($crypto['symbol'] ?? '')) . '</span><span class="crypto-name">' . e((string) ($crypto['name'] ?? '')) . '</span></div></div>';
            $html .= '<div class="crypto-price-info"><span class="crypto-price">$' . number_format($crypto['price'], 2) . '</span><span class="crypto-change ' . $changeClass . '">' . $arrow . ' ' . number_format(abs($crypto['percent_change_24h'] ?? 0), 2) . '%</span></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="crypto-widget-footer"><span class="crypto-data-source">' . e($sourceLabel) . '</span><a href="https://coinmarketcap.com/" target="_blank" rel="noopener noreferrer nofollow">View All →</a></div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render stock widget inline
     */
    private function renderStockWidget(array $data): string
    {
        $marketData = $data['data'];
        $indices = $marketData['indices'] ?? [];
        $stocks = $marketData['stocks'] ?? [];
        $status = $data['status'];
        $source = $marketData['source'] ?? 'unavailable';
        $message = $marketData['message'] ?? 'Live stock data is temporarily unavailable.';
        
        if (empty($indices) && empty($stocks)) {
            return '<div class="stock-widget"><div class="stock-widget-header"><h4 class="stock-widget-title"><span class="stock-icon">📈</span>Stock Markets</h4><span class="stock-status-badge stock-status-closed">UNAVAILABLE</span></div><div class="stock-empty-state">' . e($message) . '</div><div class="stock-widget-footer"><span class="stock-data-source">Stock data provider unavailable</span></div></div>';
        }

        $html = '<div class="stock-widget">';
        $html .= '<div class="stock-widget-header">';
        $html .= '<h4 class="stock-widget-title"><span class="stock-icon">📈</span>Stock Markets</h4>';
        $html .= '<span class="stock-status-badge stock-status-' . $status . '">' . ($status === 'open' ? 'OPEN' : 'CLOSED') . '</span>';
        $html .= '</div>';
        
        if (!empty($indices)) {
            $html .= '<div class="stock-indices">';
            foreach (array_slice($indices, 0, 3) as $index) {
                $changeClass = ($index['change'] ?? 0) >= 0 ? 'stock-up' : 'stock-down';
                $arrow = ($index['change'] ?? 0) >= 0 ? '▲' : '▼';
                $html .= '<div class="stock-index-item">';
                $html .= '<span class="stock-index-name">' . e((string) ($index['name'] ?? '')) . '</span>';
                $html .= '<div class="stock-index-values"><span class="stock-index-price">' . number_format($index['price'], 2) . '</span><span class="stock-index-change ' . $changeClass . '">' . $arrow . ' ' . number_format(abs($index['change_percent'] ?? 0), 2) . '%</span></div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        if (!empty($stocks)) {
            $html .= '<div class="stock-list">';
            foreach ($stocks as $stock) {
                $changeClass = ($stock['change'] ?? 0) >= 0 ? 'stock-up' : 'stock-down';
                $arrow = ($stock['change'] ?? 0) >= 0 ? '▲' : '▼';
                $html .= '<div class="stock-item">';
                $html .= '<div class="stock-info"><span class="stock-symbol">' . e((string) ($stock['symbol'] ?? '')) . '</span><span class="stock-name">' . e((string) ($stock['name'] ?? '')) . '</span></div>';
                $html .= '<div class="stock-price-info"><span class="stock-price">' . number_format($stock['price'], 2) . '</span><span class="stock-change ' . $changeClass . '">' . $arrow . ' ' . number_format(abs($stock['change_percent'] ?? 0), 2) . '%</span></div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $sourceLabel = match ($source) {
            'stale' => 'Showing last successful market snapshot',
            'yahoo' => 'Market snapshot via Yahoo Finance',
            'stooq' => 'Showing delayed market snapshot',
            'massive' => 'Data delayed, by Massive',
            default => 'Market data delayed',
        };

        $html .= '<div class="stock-widget-footer"><span class="stock-data-source">' . e($sourceLabel) . '</span><a href="https://finance.yahoo.com/" target="_blank" rel="noopener noreferrer nofollow">View All →</a></div>';
        $html .= '</div>';
        
        return $html;
    }
}
