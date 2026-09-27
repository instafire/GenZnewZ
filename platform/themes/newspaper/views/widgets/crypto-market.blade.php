@php
use App\Services\CryptoMarketService;

$service = new CryptoMarketService();
$payload = $service->getWidgetPayload(5);
$cryptos = $payload['cryptos'] ?? [];
$global = $payload['global'] ?? [];
$isLive = (bool) ($payload['is_live'] ?? false);
$source = $payload['source'] ?? 'fallback';
$message = $payload['message'] ?? null;
@endphp

<div class="crypto-widget">
    <div class="crypto-widget-header">
        <h4 class="crypto-widget-title">
            <span class="crypto-icon">₿</span>
            Crypto Markets
        </h4>
        <span class="crypto-live-badge">{{ $isLive ? 'LIVE' : 'DELAYED' }}</span>
    </div>

    @if($message)
        <div class="crypto-empty-state">
            {{ $message }}
        </div>
    @endif
    
    {{-- Global Market Overview --}}
    <div class="crypto-global-metrics">
        <div class="crypto-metric-item">
            <span class="crypto-metric-label">Total Cap</span>
            <span class="crypto-metric-value">${{ CryptoMarketService::formatNumber($global['total_market_cap'] ?? 0) }}</span>
        </div>
        <div class="crypto-metric-item">
            <span class="crypto-metric-label">24h Vol</span>
            <span class="crypto-metric-value">${{ CryptoMarketService::formatNumber($global['total_volume_24h'] ?? 0) }}</span>
        </div>
        <div class="crypto-metric-item">
            <span class="crypto-metric-label">BTC Dom</span>
            <span class="crypto-metric-value">{{ number_format($global['btc_dominance'] ?? 0, 1) }}%</span>
        </div>
    </div>
    
    {{-- Crypto List --}}
    <div class="crypto-list">
        @foreach($cryptos as $crypto)
        <div class="crypto-item">
            <div class="crypto-rank-info">
                <span class="crypto-rank">{{ $loop->iteration }}</span>
                <div class="crypto-info">
                    <span class="crypto-symbol">{{ $crypto['symbol'] }}</span>
                    <span class="crypto-name">{{ $crypto['name'] }}</span>
                </div>
            </div>
            <div class="crypto-price-info">
                <span class="crypto-price">{{ CryptoMarketService::formatPrice($crypto['price']) }}</span>
                <span class="crypto-change {{ CryptoMarketService::getChangeClass($crypto['percent_change_24h']) }}">
                    {{ CryptoMarketService::getChangeArrow($crypto['percent_change_24h']) }}
                    {{ number_format(abs($crypto['percent_change_24h']), 2) }}%
                </span>
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="crypto-widget-footer">
        <span class="crypto-data-source">{{ $source === 'coinmarketcap' ? 'Data by CoinMarketCap' : ($source === 'coingecko' ? 'Data by CoinGecko' : ($source === 'mixed' ? 'Live market data' : 'Fallback market snapshot')) }}</span>
        <a href="https://coinmarketcap.com/" target="_blank" rel="noopener noreferrer nofollow" class="crypto-view-all">View All Crypto Markets →</a>
    </div>
</div>
