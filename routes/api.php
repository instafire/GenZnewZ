<?php

use App\Http\Controllers\Api\LiveEventsController;
use App\Http\Controllers\Api\RssTickerController;
use Illuminate\Support\Facades\Route;

Route::get('rss-ticker', [RssTickerController::class, 'index'])->name('api.rss.ticker');

Route::middleware('throttle:60,1')->prefix('live-events')->group(function () {
    Route::get('progress', [LiveEventsController::class, 'progress']);
    Route::get('headlines', [LiveEventsController::class, 'headlines']);
    Route::get('category/{category}', [LiveEventsController::class, 'category']);
    Route::post('refresh', [LiveEventsController::class, 'refresh']);
});

// Public read API for AI agents and other machine consumers (no auth).
// Published posts only: paginated listing, single-article JSON, search,
// live topic list. Throttled at 120 requests/minute.
Route::middleware('throttle:120,1')->prefix('v1')->group(function () {
    Route::get('articles', [\App\Http\Controllers\Api\ArticleReaderController::class, 'index'])
        ->name('api.v1.articles.index');
    Route::get('topics', [\App\Http\Controllers\Api\ArticleReaderController::class, 'topics'])
        ->name('api.v1.topics');
    Route::get('articles/{slug}', [\App\Http\Controllers\Api\ArticleReaderController::class, 'show'])
        ->where('slug', '[^/]+')
        ->name('api.v1.articles.show');
});



// Webhook subscriptions: push notifications when articles are published.
// Admin-only: header X-Internal-Token must match config("automation.internal_token").
// Signing: X-GNZ-Signature = "sha256=" + hex(HMAC-SHA256(raw JSON body, subscription secret)).
Route::middleware("throttle:60,1")->prefix("v1")->group(function () {
    Route::get("webhooks", [\App\Http\Controllers\Api\WebhookController::class, "index"])
        ->name("api.v1.webhooks.index");
    Route::post("webhooks", [\App\Http\Controllers\Api\WebhookController::class, "store"])
        ->name("api.v1.webhooks.store");
    Route::delete("webhooks/{id}", [\App\Http\Controllers\Api\WebhookController::class, "destroy"])
        ->where("id", "[0-9]+")
        ->name("api.v1.webhooks.destroy");
});

