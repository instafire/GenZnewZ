<?php

use Botble\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\Author\Http\Controllers'], function (): void {
    AdminHelper::registerRoutes(function (): void {
        Route::group(['prefix' => 'authors', 'as' => 'author.'], function (): void {
            Route::resource('', 'AuthorController')->parameters(['' => 'author']);
        });
    });
});
