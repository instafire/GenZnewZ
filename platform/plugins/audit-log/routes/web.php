<?php

use Botble\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\AuditLog\Http\Controllers'], function (): void {
        Route::group(['prefix' => 'audit-logs', 'as' => 'audit-log.'], function (): void {
            Route::resource('', 'AuditLogController')
                ->parameters(['' => 'audit-log'])
                ->only(['index', 'destroy']);

            Route::get('widgets/activities', [
                'as' => 'widget.activities',
                'uses' => 'AuditLogController@getWidgetActivities',
                'permission' => 'audit-log.index',
            ]);

            Route::get('items/empty', [
                'as' => 'empty',
                'uses' => 'AuditLogController@deleteAll',
                'permission' => 'audit-log.destroy',
            ]);
        });
    });
});
