@php
use App\Services\StockMarketService;

$service = new StockMarketService();
$data = $service->getMarketData();
$indices = $data['indices'] ?? [];
$stocks = $data['stocks'] ?? [];
$marketStatus = $service->getMarketStatus();
$source = $data['source'] ?? 'unavailable';
$message = $data['message'] ?? 'Live stock data is temporarily unavailable.';
$hasMarketData = !empty($indices) || !empty($stocks);
$statusClass = $hasMarketData ? 'stock-status-' . $marketStatus : 'stock-status-closed';
$statusLabel = $hasMarketData ? ($marketStatus === 'open' ? 'OPEN' : 'CLOSED') : 'UNAVAILABLE';
@endphp

<div class="stock-widget">
    <div class="stock-widget-header">
        <h4 class="stock-widget-title">
            <span class="stock-icon">📈</span>
            Stock Markets
        </h4>
        <span class="stock-status-badge {{ $statusClass }}">
            {{ $statusLabel }}
        </span>
    </div>
    
    @if(empty($indices) && empty($stocks))
        <div class="stock-empty-state">
            {{ $message }}
        </div>
    @else
        {{-- Major Indices --}}
        <div class="stock-indices">
            @foreach(array_slice($indices, 0, 3) as $index)
            <div class="stock-index-item">
                <span class="stock-index-name">{{ $index['name'] }}</span>
                <div class="stock-index-values">
                    <span class="stock-index-price">{{ StockMarketService::formatPrice($index['price']) }}</span>
                    <span class="stock-index-change {{ StockMarketService::getChangeClass($index['change']) }}">
                        {{ StockMarketService::getChangeArrow($index['change']) }}
                        {{ number_format(abs($index['change_percent']), 2) }}%
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- Stock List --}}
        <div class="stock-list">
            @foreach($stocks as $stock)
            <div class="stock-item">
                <div class="stock-info">
                    <span class="stock-symbol">{{ $stock['symbol'] }}</span>
                    <span class="stock-name">{{ $stock['name'] }}</span>
                </div>
                <div class="stock-price-info">
                    <span class="stock-price">{{ StockMarketService::formatPrice($stock['price']) }}</span>
                    <span class="stock-change {{ StockMarketService::getChangeClass($stock['change']) }}">
                        {{ StockMarketService::getChangeArrow($stock['change']) }}
                        {{ number_format(abs($stock['change_percent']), 2) }}%
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    @endif
    
    <div class="stock-widget-footer">
        <span class="stock-data-source">
            {{ $source === 'stale' ? 'Showing last successful market snapshot' : ($source === 'massive' ? 'Data delayed, by Massive' : ($source === 'yahoo' ? 'Market snapshot via Yahoo Finance' : ($source === 'stooq' ? 'Showing delayed market snapshot' : 'Stock data provider unavailable'))) }}
        </span>
        <a href="https://finance.yahoo.com/" target="_blank" rel="noopener noreferrer nofollow" class="stock-view-all">View All Stock Markets →</a>
    </div>
</div>
