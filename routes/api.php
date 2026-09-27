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


