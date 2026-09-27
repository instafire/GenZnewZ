<?php

namespace App\Services;

use Botble\Setting\Facades\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IndexNowService
{
    /**
     * IndexNow endpoints.
     *
     * `api.indexnow.org` is the shared gateway: one submission there is fanned
     * out to every participating engine (Bing, Yandex, Seznam and others).
     * Posting to each engine separately is redundant and it multiplied the
     * latency of the request that triggers it.
     *
     * Per-engine endpoints stay available via config('indexnow.endpoints') if a
     * specific engine ever needs a direct submission. Naver is deliberately not a
     * default: searchadvisor.naver.com answers `422 Invalid urls` unless the site
     * is registered with Naver Search Advisor, and that single failure used to
     * make a fully successful Bing submission look like it had failed.
     */
    protected array $endpoints = [
        'indexnow' => 'https://api.indexnow.org/indexnow',
    ];

    public function __construct()
    {
        $configured = config('indexnow.endpoints');

        if (is_array($configured) && $configured !== []) {
            $this->endpoints = $configured;
        }
    }

    /**
     * Submit URL to IndexNow for instant indexing
     *
     * @param string $url The URL to submit
     * @param string $type The type of update (updated, deleted)
     * @return bool Success status
     */
    public function submit(string $url, string $type = 'updated'): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if (! $this->isSubmittable($url)) {
            Log::debug('IndexNow: skipping URL outside the configured production host', ['url' => $url]);

            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $key = $this->getKey();
        $keyLocation = $this->getKeyLocation();

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $keyLocation,
            'urlList' => [$url],
        ];

        $delivered = false;

        foreach ($this->endpoints as $name => $endpoint) {
            try {
                // Short timeout on purpose: this runs inside the publish request,
                // so a slow engine must not hold up the agent that just published.
                $response = Http::timeout($this->timeout())
                    ->withHeaders([
                        'Content-Type' => 'application/json; charset=utf-8',
                    ])
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $delivered = true;
                    Log::info("IndexNow: Successfully submitted {$url} to {$name}");
                } else {
                    Log::warning("IndexNow: Failed to submit {$url} to {$name}. Status: " . $response->status(), [
                        'body' => Str::limit((string) $response->body(), 200),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("IndexNow: Error submitting to {$name}: " . $e->getMessage());
            }
        }

        /**
         * True if at least one engine accepted it. Reporting failure because one
         * optional engine rejected the payload hides the engines that took it.
         */
        return $delivered;
    }

    /**
     * Submit multiple URLs to IndexNow
     *
     * @param array $urls Array of URLs to submit
     * @param string $type The type of update (updated, deleted)
     * @return bool Success status
     */
    public function submitBulk(array $urls, string $type = 'updated'): bool
    {
        if (! $this->isEnabled() || empty($urls)) {
            return false;
        }

        // IndexNow has a limit of 10,000 URLs per request
        $urls = array_slice($urls, 0, 10000);

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $key = $this->getKey();
        $keyLocation = $this->getKeyLocation();

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $keyLocation,
            'urlList' => $urls,
        ];

        $delivered = false;

        foreach ($this->endpoints as $name => $endpoint) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json; charset=utf-8',
                    ])
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $delivered = true;
                    Log::info("IndexNow: Successfully submitted " . count($urls) . " URLs to {$name}");
                } else {
                    Log::warning("IndexNow: Failed to submit bulk to {$name}. Status: " . $response->status(), [
                        'body' => Str::limit((string) $response->body(), 200),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("IndexNow: Error submitting bulk to {$name}: " . $e->getMessage());
            }
        }

        return $delivered;
    }

    /**
     * Timeout for IndexNow calls, in seconds.
     *
     * `submit()` runs inside the publish request, so this is deliberately short:
     * the notification must never make an agent wait on a slow search engine.
     */
    protected function timeout(): int
    {
        return max(1, (int) config('indexnow.timeout', 5));
    }

    /**
     * Only submit URLs on the site's own configured host. Without this, a local,
     * staging or test environment would push its own URLs to Bing.
     */
    protected function isSubmittable(string $url): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $urlHost && $appHost && strcasecmp($urlHost, $appHost) === 0;
    }

    /**
     * Generate a random IndexNow key
     *
     * @return string
     */
    public function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get the IndexNow key from settings
     *
     * @return string
     */
    public function getKey(): string
    {
        $key = setting('indexnow_key');

        if (! $key) {
            $key = $this->generateKey();

            // Setting::set(), not setting([...]). The array form does not exist on
            // the setting() helper - it threw a TypeError, so this branch crashed
            // instead of generating a key whenever indexnow_key was missing.
            Setting::set('indexnow_key', $key)->save();

            // Pass the key through rather than letting createKeyFile() re-resolve
            // it, which would recurse if the write had not landed yet.
            $this->createKeyFile($key);
        }

        return $key;
    }

    /**
     * Get the key location URL
     *
     * @return string
     */
    public function getKeyLocation(): string
    {
        return url($this->getKey() . '.txt');
    }

    /**
     * Check if IndexNow is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return setting('indexnow_enabled', true);
    }

    /**
     * Create the key file for verification
     *
     * @return bool
     */
    public function createKeyFile(?string $key = null): bool
    {
        $key = $key ?: $this->getKey();

        // Never write into the real web root from a test or local run. public_path()
        // is absolute, so an unguarded call during a test run litters production
        // with junk key files - it did exactly that before this guard existed.
        if (! $this->mayWritePublicFiles()) {
            return false;
        }

        $path = public_path($key . '.txt');

        try {
            file_put_contents($path, $key);

            return true;
        } catch (\Exception $e) {
            Log::error('IndexNow: Failed to create key file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * The key file is a public artifact, so it may only be written by a genuine
     * production request. Tests, local runs and staging all point public_path()
     * at the same absolute directory, which is how they polluted production.
     */
    protected function mayWritePublicFiles(): bool
    {
        return ! app()->runningUnitTests()
            && app()->environment('production')
            && $this->isSubmittable((string) config('app.url'));
    }
}
