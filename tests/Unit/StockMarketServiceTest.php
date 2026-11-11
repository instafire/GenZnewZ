<?php

namespace Tests\Unit;

use App\Services\StockMarketService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StockMarketServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.massive.api_key' => 'test-key']);
    }

    public function test_all_providers_failing_returns_unavailable_state(): void
    {
        Http::fake([
            'api.massive.app/*' => Http::response([], 500),
            'query2.finance.yahoo.com/*' => Http::response([], 500),
            'stooq.com/*' => Http::response([], 500),
        ]);

        $data = (new StockMarketService())->getMarketData();

        $this->assertFalse($data['is_live']);
        $this->assertSame('unavailable', $data['source']);
        $this->assertSame([], $data['indices']);
        $this->assertSame([], $data['stocks']);
        $this->assertNotNull($data['message']);
    }

    public function test_provider_exceptions_return_unavailable_state(): void
    {
        Http::fake([
            '*' => function () {
                // A plain \Exception trips Laravel's HTTP-client promise chain:
                // PendingRequest::makePromise() only accepts TransferException
                // (and friends) in its rejection handler, so a generic Exception
                // escapes as a TypeError before the service's catch can run.
                // A ConnectException is what Guzzle actually throws when the
                // network is down, so this is the realistic failure shape.
                throw new \GuzzleHttp\Exception\ConnectException(
                    'network down',
                    new \GuzzleHttp\Psr7\Request('GET', 'https://api.massive.app/stocks/prices')
                );
            },
        ]);

        $data = (new StockMarketService())->getMarketData();

        $this->assertFalse($data['is_live']);
        $this->assertSame('unavailable', $data['source']);
    }

    public function test_yahoo_failure_falls_through_to_stooq(): void
    {
        config(['services.massive.api_key' => '']);

        Http::fake([
            'query2.finance.yahoo.com/*' => Http::response([], 500),
            'stooq.com/*' => Http::response(
                "Symbol,Date,Open,High,Low,Close,Volume\nAAPL.US,2026-10-03,228,230,227,229,900000\nAAPL.US,2026-10-05,230,232,229,231,1000000\n",
                200,
                ['Content-Type' => 'text/csv']
            ),
        ]);

        $data = (new StockMarketService())->getMarketData();

        $this->assertFalse($data['is_live']);
        $this->assertSame('stooq', $data['source']);
        $this->assertNotEmpty($data['stocks']);
    }
}
