<?php

namespace Tests\Feature;

use App\Services\IndexNowService;
use Botble\Setting\Facades\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * IndexNow is submitted inline on the publish path, so it must be fast, it must
 * only ever talk about this site's own URLs, and a single unhappy engine must
 * not make a successful submission look like a failure.
 */
class IndexNowSubmissionTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * A missing key must be generated and persisted rather than crashing - the
     * old code called an array form of setting() that does not exist.
     */
    public function test_it_generates_and_stores_a_key_when_missing(): void
    {
        Setting::set('indexnow_key', null)->save();

        $service = $this->service();
        $key = $service->getKey();

        $this->assertNotEmpty($key);
        $this->assertSame($key, setting('indexnow_key'));
        $this->assertStringEndsWith($key . '.txt', $service->getKeyLocation());
    }

    public function test_it_submits_to_the_shared_indexnow_gateway(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

        $articleUrl = url('/a-real-article');

        $this->assertTrue($this->service()->submit($articleUrl, 'updated'));

        Http::assertSent(function ($request) use ($articleUrl): bool {
            return str_contains($request->url(), 'api.indexnow.org')
                && $request['host'] === parse_url(config('app.url'), PHP_URL_HOST)
                && $request['urlList'] === [$articleUrl]
                && $request['keyLocation'] === $this->service()->getKeyLocation();
        });
    }

    public function test_it_defaults_to_a_single_endpoint(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $this->service()->submit(url('/a-real-article'));

        // One call to the shared gateway, not one per search engine.
        Http::assertSentCount(1);
    }

    /**
     * Regression: a per-engine failure used to make submit() report false even
     * when the gateway accepted the URL, hiding a working Bing submission.
     */
    public function test_a_single_failing_engine_does_not_mask_a_successful_one(): void
    {
        config(['indexnow.endpoints' => [
            'gateway' => 'https://gateway.example/indexnow',
            'picky' => 'https://picky.example/indexnow',
        ]]);

        Http::fake([
            'gateway.example/*' => Http::response('', 200),
            'picky.example/*' => Http::response('Invalid urls', 422),
        ]);

        $this->assertTrue(
            $this->service()->submit(url('/a-real-article')),
            'One engine accepting the URL is a successful submission.'
        );
    }

    public function test_it_reports_failure_when_every_engine_rejects(): void
    {
        config(['indexnow.endpoints' => [
            'gateway' => 'https://gateway.example/indexnow',
        ]]);

        Http::fake(['gateway.example/*' => Http::response('nope', 500)]);

        $this->assertFalse($this->service()->submit(url('/a-real-article')));
    }

    /**
     * Without this guard a local or staging environment would push its own
     * URLs to a real search engine.
     */
    public function test_it_never_submits_urls_from_another_host(): void
    {
        Http::fake();

        $this->assertFalse($this->service()->submit('http://localhost/not-real'));
        $this->assertFalse($this->service()->submit('http://127.0.0.1:8000/not-real'));
        $this->assertFalse($this->service()->submit('https://staging.example.com/not-real'));

        Http::assertNothingSent();
    }

    public function test_it_does_nothing_when_disabled(): void
    {
        Setting::set('indexnow_enabled', false)->save();

        Http::fake();

        $this->assertFalse($this->service()->submit(url('/a-real-article')));

        Http::assertNothingSent();
    }

    public function test_bulk_submission_reports_success_and_sends_the_url_list(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

        $urls = [url('/one'), url('/two')];

        $this->assertTrue($this->service()->submitBulk($urls));

        Http::assertSent(fn ($request): bool => $request['urlList'] === $urls);
    }

    protected function service(): IndexNowService
    {
        return new IndexNowService();
    }
}
