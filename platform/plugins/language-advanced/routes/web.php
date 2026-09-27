<?php

use Botble\Base\Facades\AdminHelper;
use Botble\LanguageAdvanced\Http\Controllers\LanguageAdvancedController;
use Botble\LanguageAdvanced\Http\Controllers\TranslationExportController;
use Botble\LanguageAdvanced\Http\Controllers\TranslationImportController;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group([
        'controller' => LanguageAdvancedController::class,
        'prefix' => 'language-advanced',
    ], function (): void {
        Route::post('save/{id}', [
            'as' => 'language-advanced.save',
            'uses' => 'save',
            'permission' => false,
        ])->wherePrimaryKey();
    });

    // Base route for translation imports and exports
    Route::prefix('tools/data-synchronize')->name('tools.data-synchronize.')->group(function (): void {
        Route::prefix('import')->name('import.')->group(function (): void {
            Route::group(['prefix' => 'translations/{type}', 'as' => 'translations.', 'permission' => 'translations.import'], function (): void {
                Route::get('/', ['uses' => TranslationImportController::class . '@index', 'as' => 'index']);
                Route::post('validate', ['uses' => TranslationImportController::class . '@validateData', 'as' => 'validate']);
                Route::post('/', ['uses' => TranslationImportController::class . '@import', 'as' => 'store']);
                Route::post('download-example', ['uses' => TranslationImportController::class . '@downloadExample', 'as' => 'download-example']);
            });
        });

        Route::prefix('export')->name('export.')->group(function (): void {
            Route::group(['prefix' => 'translations/{type}', 'as' => 'translations.', 'permission' => 'translations.export'], function (): void {
                Route::get('/', ['uses' => TranslationExportController::class . '@index', 'as' => 'index']);
                Route::post('/', ['uses' => TranslationExportController::class . '@store', 'as' => 'store']);
            });
        });
    });
});
