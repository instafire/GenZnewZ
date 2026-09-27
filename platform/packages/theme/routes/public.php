<?php

use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Events\ThemeRoutingAfterEvent;
use Botble\Theme\Events\ThemeRoutingBeforeEvent;
use Botble\Theme\Facades\SiteMapManager;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::group(['controller' => PublicController::class], function (): void {
        event(new ThemeRoutingBeforeEvent(app()->make('router')));

        Route::get('/', 'getIndex')->name('public.index');

        if (setting('sitemap_enabled', true)) {
            Route::get('sitemap.xml', 'getSiteMap')->name('public.sitemap');

            Route::get('{key}.{extension}', 'getSiteMapIndex')
                ->whereIn('extension', SiteMapManager::allowedExtensions())
                ->where('key', SiteMapManager::getKeysRegex())
                ->name('public.sitemap.index');
        }

        Route::get('{slug?}', 'getView')->name('public.single');

        Route::get('{prefix}/{slug?}', 'getViewWithPrefix')
            ->whereIn('prefix', SlugHelper::getAllPrefixes() ?: ['1437bcd2-d94e-4a5fd-9a39-b5d60225e9af']);

        event(new ThemeRoutingAfterEvent(app()->make('router')));
    });
});
