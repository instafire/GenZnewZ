<?php

namespace Tests\Unit;

use App\Services\CryptoMarketService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CryptoMarketServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.coinmarketcap.api_key' => '']);
    }

    public function test_coingecko_http_failure_returns_fallback_snapshot(): void
    {
        Http::fake([
            'api.coingecko.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $data = (new CryptoMarketService())->getTopCryptos(5);

        $this->assertNotEmpty($data);
        $this->assertSame('BTC', $data[0]['symbol']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'coingecko.com'));
    }

    public function test_coingecko_connection_exception_returns_fallback_snapshot(): void
    {
        Http::fake([
            'api.coingecko.com/*' => function () {
                throw new \Exception('connection refused');
            },
        ]);

        $data = (new CryptoMarketService())->getTopCryptos(5);

        $this->assertNotEmpty($data);
        $this->assertSame('BTC', $data[0]['symbol']);
    }

    public function test_coinmarketcap_http_failure_falls_back_to_coingecko(): void
    {
        config(['services.coinmarketcap.api_key' => 'test-key']);

        Http::fake([
            'pro-api.coinmarketcap.com/*' => Http::response(['status' => ['error_code' => 500]], 500),
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'symbol' => 'btc',
                    'current_price' => 60000,
                    'price_change_percentage_24h_in_currency' => 1.5,
                    'market_cap' => 1100000000000,
                    'total_volume' => 30000000000,
                    'circulating_supply' => 19000000,
                    'last_updated' => '2026-10-06T00:00:00.000Z',
                ],
            ], 200),
            'api.coingecko.com/api/v3/global*' => Http::response([
                'data' => [
                    'total_market_cap' => ['usd' => 2000000000000],
                    'total_volume' => ['usd' => 80000000000],
                    'market_cap_percentage' => ['btc' => 55.0, 'eth' => 13.0],
                    'active_cryptocurrencies' => 9000,
                ],
            ], 200),
        ]);

        $payload = (new CryptoMarketService())->getWidgetPayload(5);

        $this->assertTrue($payload['is_live']);
        $this->assertSame('coingecko', $payload['source']);
        $this->assertSame('BTC', $payload['cryptos'][0]['symbol']);
        $this->assertSame(60000.0, $payload['cryptos'][0]['price']);
    }

    public function test_all_providers_down_widget_reports_fallback(): void
    {
        config(['services.coinmarketcap.api_key' => 'test-key']);

        Http::fake([
            'pro-api.coinmarketcap.com/*' => Http::response([], 500),
            'api.coingecko.com/*' => function () {
                throw new \Exception('down');
            },
        ]);

        $payload = (new CryptoMarketService())->getWidgetPayload(5);

        $this->assertFalse($payload['is_live']);
        $this->assertSame('fallback', $payload['source']);
        $this->assertNotNull($payload['message']);
        $this->assertNotEmpty($payload['cryptos']);
    }
}
