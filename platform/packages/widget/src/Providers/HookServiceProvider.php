<?php

namespace Botble\Widget\Providers;

use Botble\Base\Facades\AdminHelper;
use Botble\Base\Supports\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {

        add_filter(DASHBOARD_FILTER_ADMIN_NOTIFICATIONS, function (?string $html) {
            if (! AdminHelper::isInAdmin(true) || ! Auth::check()) {
                return $html;
            }

            // Don't show the suggestion on the settings page
            if (Route::currentRouteName() === 'settings.cache') {
                return $html;
            }

            // Check if widget caching is disabled
            if (setting('widget_cache_enabled', false)) {
                return $html;
            }

            // Check if the suggestion has been dismissed
            if (isset($_COOKIE['widget_cache_suggestion_dismissed']) && $_COOKIE['widget_cache_suggestion_dismissed'] == '1') {
                return $html;
            }

            return $html . view('packages/widget::partials.widget-cache-suggestion')->render();
        }, 6);
    }
}
