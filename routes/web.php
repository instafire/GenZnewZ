<?php

use App\Http\Controllers\HomepageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


// Widget AJAX Routes
Route::get('/widgets/crypto', [\App\Http\Controllers\WidgetController::class, 'cryptoWidgetHtml'])->name('widgets.crypto');
Route::get('/widgets/stock', [\App\Http\Controllers\WidgetController::class, 'stockWidgetHtml'])->name('widgets.stock');
Route::get('/home/categories-chunk', [HomepageController::class, 'categoriesChunk'])
    ->middleware('throttle:60,1')
    ->name('home.categories.chunk');

// Public uploads run in the web stack so CSRF and session throttling apply.
Route::post('/api/upload', [\App\Http\Controllers\UploadController::class, 'upload'])
    ->middleware('throttle:6,1')
    ->name('api.upload');

// Explicit contact page route so the URL and route name remain stable even if
// theme-provider route registration order changes.
Route::get('/contact', function () {
    return \Botble\Theme\Facades\Theme::scope('templates.contact')->render();
})->name('contact.page');

// Explicit DMCA route to avoid catch-all slug conflicts.
Route::get('/dmca', function () {
    return \Botble\Theme\Facades\Theme::scope('templates.dmca')->render();
})->name('dmca.page');

// User behavior tracking endpoint for personalized recommendations.
// Placed in web.php so it is not blocked by the Botble API enabled middleware.
Route::post('/api/tracking/event', [\App\Http\Controllers\Api\TrackingController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('api.tracking.event');

Route::redirect('/news', '/search', 301);

// Widget HTML endpoints
Route::get('/widgets/crypto-html', [App\Http\Controllers\WidgetController::class, 'cryptoWidgetHtml'])->name('widgets.crypto.html');
Route::get('/widgets/stock-html', [App\Http\Controllers\WidgetController::class, 'stockWidgetHtml'])->name('widgets.stock.html');

// Clean Markdown rendering of one article for AI consumers. Article slugs
// exclude dots, so this route never clashes with the catch-all blog slug.
Route::get('{slug}.md', [\App\Http\Controllers\Api\ArticleReaderController::class, 'markdown'])
    ->where('slug', '[^/.]+')
    ->middleware('throttle:60,1')
    ->name('article.markdown');
