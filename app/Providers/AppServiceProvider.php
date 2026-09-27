<?php

namespace App\Providers;

use App\Observers\PostImageObserver;
use App\Services\AutomationQuotaService;
use Botble\Base\Facades\AdminHelper;
use Botble\Base\Facades\Assets;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // File cache writes can fail after housekeeping jobs remove the sharded
        // directory. Recreate it before the first request needs the cache store.
        File::ensureDirectoryExists(storage_path('framework/cache/data'));

        $this->registerAutomationRateLimiters();

        if (class_exists(\Botble\Blog\Models\Post::class)) {
            \Botble\Blog\Models\Post::observe(PostImageObserver::class);
        }

        if (
            function_exists('add_action')
            && defined('BASE_ACTION_ENQUEUE_SCRIPTS')
            && class_exists(Assets::class)
            && class_exists(AdminHelper::class)
        ) {
            add_action(BASE_ACTION_ENQUEUE_SCRIPTS, function (): void {
                if (! AdminHelper::isInAdmin()) {
                    return;
                }

                $adminCssVersion = file_exists(public_path('css/admin-nyt.css'))
                    ? filemtime(public_path('css/admin-nyt.css'))
                    : null;
                $adminJsVersion = file_exists(public_path('js/admin-nyt.js'))
                    ? filemtime(public_path('js/admin-nyt.js'))
                    : null;

                $adminCssPath = 'css/admin-nyt.css' . ($adminCssVersion ? '?v=' . $adminCssVersion : '');
                $adminJsPath = 'js/admin-nyt.js' . ($adminJsVersion ? '?v=' . $adminJsVersion : '');

                Assets::addStylesDirectly($adminCssPath)
                    ->addScriptsDirectly($adminJsPath);
            }, 199);
        }
    }

    /**
     * Rate limiters for the public automation API.
     *
     * GET /api/v1/automation/status advertised rate limiting that did not exist:
     * the routes had no throttle middleware, so an anonymous caller could register
     * accounts or publish without bound. These limiters make the advertised
     * numbers real and are the values /status now reports.
     *
     * The 429 body matches the rest of the API (success/message/retry_after) so an
     * agent can parse it without special-casing throttle responses.
     */
    protected function registerAutomationRateLimiters(): void
    {
        $quota = app(AutomationQuotaService::class);

        $deny = function (Request $request, array $headers) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Slow down and retry after the window resets.',
                'retry_after' => (int) ($headers['Retry-After'] ?? 60),
            ], 429, $headers);
        };

        RateLimiter::for(AutomationQuotaService::REGISTER, function (Request $request) use ($quota, $deny) {
            return Limit::perHour($quota->limit(AutomationQuotaService::REGISTER))
                ->by($quota->key(AutomationQuotaService::REGISTER, $request))
                ->response($deny);
        });

        RateLimiter::for(AutomationQuotaService::AUTH, function (Request $request) use ($quota, $deny) {
            return Limit::perMinute($quota->limit(AutomationQuotaService::AUTH))
                ->by($quota->key(AutomationQuotaService::AUTH, $request))
                ->response($deny);
        });

        RateLimiter::for(AutomationQuotaService::READ, function (Request $request) use ($quota, $deny) {
            return Limit::perMinute($quota->limit(AutomationQuotaService::READ))
                ->by($quota->key(AutomationQuotaService::READ, $request))
                ->response($deny);
        });

        RateLimiter::for(AutomationQuotaService::PUBLISH, function (Request $request) use ($quota, $deny) {
            return Limit::perHour($quota->limit(AutomationQuotaService::PUBLISH))
                ->by($quota->key(AutomationQuotaService::PUBLISH, $request))
                ->response($deny);
        });
    }
}
