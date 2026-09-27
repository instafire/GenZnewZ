<?php

use App\Services\CryptoMarketService;
use App\Services\HomepageDataService;
use App\Services\StockMarketService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\AIReporter;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('ai-reporter:check {email} {password} {--rotate-token}', function () {
    $email = $this->argument('email');
    $password = $this->argument('password');
    $rotateToken = $this->option('rotate-token');

    $reporter = AIReporter::where('email', $email)->first();

    if (!$reporter) {
        $this->error('AI reporter not found.');
        return;
    }

    if (!Hash::check($password, $reporter->password)) {
        $this->error('Password check failed.');
        return;
    }

    if (!$reporter->isActive()) {
        $this->warn('Reporter is not active. Status: ' . $reporter->status);
        return;
    }

    $this->info('Reporter credentials and status verified.');

    if ($rotateToken) {
        $newToken = AIReporter::generateApiToken();
        $reporter->update(['api_token' => $newToken]);
        $this->info('API token rotated: ' . $newToken);
    } else {
        $this->info('Dry run only. Use --rotate-token to rotate the API token.');
    }
})->purpose('Validate AI reporter login and optionally rotate API token');

Schedule::command('seo:ping-search-engines --regenerate-sitemaps')
    ->hourly()
    ->withoutOverlapping();

Schedule::call(function (): void {
    try {
        app(CryptoMarketService::class)->getTopCryptos(5);
        app(CryptoMarketService::class)->getGlobalMetrics();
        app(StockMarketService::class)->getMarketData();
    } catch (\Throwable $e) {
        Log::warning('Market widget cache prewarm failed', [
            'message' => $e->getMessage(),
        ]);
    }
})
    ->name('prewarm-market-widgets')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::call(function (): void {
    try {
        // Warm the homepage data in-process. A self-HTTP request adds network/DNS/TLS
        // overhead and can deadlock behind the same overloaded web worker it is
        // intended to warm.
        app(HomepageDataService::class)->getHomepageData();
    } catch (\Throwable $e) {
        Log::warning('Homepage cache prewarm failed', [
            'message' => $e->getMessage(),
        ]);
    }
})
    ->name('prewarm-homepage')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('behavior-logs:clean')
    ->daily()
    ->withoutOverlapping();

// A news site that stops publishing loses crawl priority, and that is invisible
// without a check. Hourly, because the usefulness of this alert is measured in
// hours - the cooldown inside the command stops it becoming noise.
Schedule::command('content:freshness-watchdog')
    ->hourly()
    ->withoutOverlapping();
