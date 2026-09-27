<?php

use Botble\Theme\Facades\Theme;

// Theme routes are now registered in ThemeServiceProvider via ThemeRoutingBeforeEvent
// This ensures they are loaded BEFORE the catch-all {slug?} route

Theme::routes();

// DMCA/Copyright Policy Route
Route::get('dmca', function () {
    return Theme::scope('templates.dmca')->render();
})->name('dmca');
