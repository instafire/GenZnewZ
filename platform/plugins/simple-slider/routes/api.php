<?php

use Botble\SimpleSlider\Http\Controllers\API\SimpleSliderController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1/simple-sliders',
    'namespace' => 'Botble\SimpleSlider\Http\Controllers\API',
], function (): void {
    Route::get('/', [SimpleSliderController::class, 'index']);
});
