<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveWorldEventsServiceOptimized;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LiveEventsController extends Controller
{
    public function progress(LiveWorldEventsServiceOptimized $service): JsonResponse
    {
        return response()->json($service->getLoadingProgress());
    }

    public function headlines(LiveWorldEventsServiceOptimized $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->getAllCachedHeadlines(),
            'cached_at' => now()->toIso8601String(),
        ]);
    }

    public function category(string $category, LiveWorldEventsServiceOptimized $service): JsonResponse
    {
        $cached = $service->getCachedCategoryHeadlines($category);

        if (! $cached) {
            return response()->json([
                'success' => false,
                'message' => 'Category data not yet available',
                'loading' => true,
            ], 202);
        }

        return response()->json([
            'success' => true,
            'data' => $cached,
        ]);
    }

    public function refresh(Request $request, LiveWorldEventsServiceOptimized $service): JsonResponse
    {
        if (! $this->mayTriggerRefresh($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        // Defence in depth on top of the freshness gate inside dispatchFeedFetchJobs():
        // bound a triggerable endpoint to one dispatch round per cooldown window, so
        // repeated calls cannot keep re-queueing work.
        $cooldown = max(0, (int) config('automation.refresh_cooldown_seconds', 60));
        $lockKey = 'live_events_refresh_lock';

        if ($cooldown > 0 && ! Cache::add($lockKey, now()->getTimestamp(), now()->addSeconds($cooldown))) {
            return response()->json([
                'success' => true,
                'message' => 'Refresh skipped, one already ran recently',
                'skipped' => true,
            ]);
        }

        $service->dispatchFeedFetchJobs();

        return response()->json([
            'success' => true,
            'message' => 'Background refresh jobs dispatched',
        ]);
    }

    /**
     * Authorise a cache-refresh request.
     *
     * Two kinds of caller exist. A trusted server-side caller (cron, uptime monitor)
     * can present the internal token. The live-events page itself cannot: a browser
     * would have to hold the secret, which means publishing it, and that is the reason
     * this endpoint used to 403 on every auto-refresh.
     *
     * Unauthenticated calls are therefore allowed as long as they are not cross-origin.
     * The work is bounded server-side (dispatchFeedFetchJobs() only queues a category
     * whose cache is missing or at least 12 minutes old, plus the cooldown and route
     * throttle above), so this is not an amplification vector.
     */
    protected function mayTriggerRefresh(Request $request): bool
    {
        if ($this->hasValidInternalToken($request)) {
            return true;
        }

        // Browsers always attach Origin to a cross-origin POST, so this stops a
        // third-party page from silently triggering refreshes in a visitor's browser.
        // When neither header is present (non-browser clients, or privacy tooling that
        // strips them) we allow the call through, because the server-side gate above
        // already bounds its cost.
        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');

        return $origin === null || $origin === '' || $this->originMatchesApp($request, (string) $origin);
    }

    protected function hasValidInternalToken(Request $request): bool
    {
        // config(), not env(): env() resolves to null when the config is cached,
        // which would make this endpoint return 403 on every call in production.
        $expected = (string) config('automation.internal_token', '');
        $provided = (string) $request->header('X-Internal-Token', '');

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }

    protected function originMatchesApp(Request $request, string $origin): bool
    {
        $originHost = parse_url($origin, PHP_URL_HOST);

        if (! is_string($originHost) || $originHost === '') {
            return false;
        }

        $allowedHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            $request->getHost(),
        ]);

        $normalise = fn (string $host): string => preg_replace('/^www\./i', '', strtolower($host));

        return in_array($normalise($originHost), array_map($normalise, $allowedHosts), true);
    }
}
