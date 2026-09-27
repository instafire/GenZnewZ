<?php

use Botble\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\Sitemap\Http\Controllers'], function (): void {
        Route::group(['prefix' => 'settings'], function (): void {
            Route::group(['prefix' => 'sitemap'], function (): void {
                Route::get('', [
                    'as' => 'sitemap.settings',
                    'uses' => 'SitemapSettingController@edit',
                ]);

                Route::put('', [
                    'as' => 'sitemap.settings.update',
                    'uses' => 'SitemapSettingController@update',
                    'permission' => 'sitemap.settings',
                ]);

                Route::post('generate-key', [
                    'as' => 'sitemap.settings.generate-key',
                    'uses' => 'SitemapSettingController@generateKey',
                    'permission' => 'sitemap.settings',
                ]);

                Route::post('create-key-file', [
                    'as' => 'sitemap.settings.create-key-file',
                    'uses' => 'SitemapSettingController@createKeyFile',
                    'permission' => 'sitemap.settings',
                ]);
            });
        });
    });
});
