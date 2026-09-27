<?php

use Botble\Language\Http\Controllers\API\LanguageController;
use Botble\Language\Http\Middleware\ApiLanguageMiddleware;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', ApiLanguageMiddleware::class],
    'prefix' => 'api/v1/languages',
    'namespace' => 'Botble\Language\Http\Controllers\API',
], function (): void {
    Route::get('/', [LanguageController::class, 'index']);
    Route::get('/current', [LanguageController::class, 'getCurrentLanguage']);
});
