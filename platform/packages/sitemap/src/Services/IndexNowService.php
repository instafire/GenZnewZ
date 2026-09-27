<?php

namespace Botble\Sitemap\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IndexNowService
{
    protected array $endpoints;

    protected bool $enabled;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->enabled = setting('indexnow_enabled', config('packages.sitemap.config.indexnow_enabled', true));
        $this->endpoints = config('packages.sitemap.config.indexnow_endpoints', []);
        $this->apiKey = setting('indexnow_api_key');
    }

    public function submitUrls(array $urls): array
    {
        if (! $this->enabled) {
            return ['status' => 'disabled', 'message' => 'IndexNow is disabled'];
        }

        if (! $this->apiKey) {
            return ['status' => 'error', 'message' => 'IndexNow API key is not configured'];
        }

        $results = [];

        foreach ($this->endpoints as $engine => $endpoint) {
            $results[$engine] = $this->submitToIndexNow($engine, $endpoint, $urls);
        }

        return $results;
    }

    public function submitSitemap(?string $sitemapUrl = null): array
    {
        $sitemapUrl = $sitemapUrl ?: url('sitemap.xml');

        return $this->submitUrls([$sitemapUrl]);
    }

    protected function submitToIndexNow(string $engine, string $endpoint, array $urls): array
    {
        try {
            $host = parse_url($urls[0], PHP_URL_HOST);

            $payload = [
                'host' => $host,
                'key' => $this->apiKey,
                'urlList' => $urls,
            ];

            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => "Successfully submitted to {$engine}",
                    'status_code' => $response->status(),
                    'urls_count' => count($urls),
                ];
            } else {
                Log::warning("Failed to submit URLs to {$engine} via IndexNow", [
                    'urls' => $urls,
                    'endpoint' => $endpoint,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return [
                    'status' => 'error',
                    'message' => "Failed to submit to {$engine}: HTTP {$response->status()}",
                    'status_code' => $response->status(),
                ];
            }
        } catch (ConnectionException $e) {
            Log::error("Connection error while submitting to {$engine} via IndexNow", [
                'urls' => $urls,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => "Connection error while submitting to {$engine}: {$e->getMessage()}",
            ];
        } catch (RequestException $e) {
            Log::error("Request error while submitting to {$engine} via IndexNow", [
                'urls' => $urls,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => "Request error while submitting to {$engine}: {$e->getMessage()}",
            ];
        } catch (Exception $e) {
            Log::error("Unexpected error while submitting to {$engine} via IndexNow", [
                'urls' => $urls,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => "Unexpected error while submitting to {$engine}: {$e->getMessage()}",
            ];
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function generateApiKey(): string
    {
        // Remove old key file if it exists
        $this->removeOldKeyFile();

        $key = Str::uuid()->toString();
        setting()->set('indexnow_api_key', $key)->save();
        $this->apiKey = $key;

        // Create new key file
        $this->createKeyFile($key);

        return $key;
    }

    public function getApiKeyFileContent(): string
    {
        return $this->apiKey ?: '';
    }

    public function getApiKeyFileName(): string
    {
        return ($this->apiKey ?: 'missing') . '.txt';
    }

    public function getKeyFilePath(): string
    {
        return public_path($this->getApiKeyFileName());
    }

    public function createKeyFile(string $apiKey): bool
    {
        try {
            $filePath = public_path($apiKey . '.txt');
            $result = File::put($filePath, $apiKey);

            if ($result !== false) {
                return true;
            }

            Log::error('Failed to create IndexNow API key file', [
                'file_path' => $filePath,
                'api_key' => $apiKey,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Exception while creating IndexNow API key file', [
                'error' => $e->getMessage(),
                'api_key' => $apiKey,
            ]);

            return false;
        }
    }

    public function removeOldKeyFile(): bool
    {
        if (! $this->apiKey) {
            return true;
        }

        try {
            $filePath = $this->getKeyFilePath();

            if (File::exists($filePath)) {
                return File::delete($filePath);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Exception while removing old IndexNow API key file', [
                'error' => $e->getMessage(),
                'file_path' => $this->getKeyFilePath(),
            ]);

            return false;
        }
    }

    public function keyFileExists(): bool
    {
        if (! $this->apiKey) {
            return false;
        }

        return File::exists($this->getKeyFilePath());
    }

    public function validateKeyFile(): bool
    {
        if (! $this->keyFileExists()) {
            return false;
        }

        try {
            $content = File::get($this->getKeyFilePath());

            return trim($content) === $this->apiKey;
        } catch (Exception $e) {
            Log::error('Exception while validating IndexNow API key file', [
                'error' => $e->getMessage(),
                'file_path' => $this->getKeyFilePath(),
            ]);

            return false;
        }
    }
}
