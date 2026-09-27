<?php

use Botble\Base\Http\Middleware\RequiresJsonRequestMiddleware;
use Botble\Shortcode\Http\Controllers\ShortcodeController;
use Botble\Shortcode\Http\Middleware\ShortcodePerformanceMiddleware;
use Botble\Theme\Facades\Theme;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::post('ajax/render-ui-blocks', [ShortcodeController::class, 'ajaxRenderUiBlock'])
        ->name('public.ajax.render-ui-block')
        ->middleware([
            'throttle:60,1',
            RequiresJsonRequestMiddleware::class,
            ShortcodePerformanceMiddleware::class
        ])
        ->withoutMiddleware(VerifyCsrfToken::class);
});
