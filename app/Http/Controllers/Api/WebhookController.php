<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Admin-only webhook subscription management.
     *
     * Auth reuses the established shared-secret pattern from the live-events
     * refresh endpoint: header X-Internal-Token compared (timing-safe)
     * against config('automation.internal_token'). Read via config(), never
     * env(), because env() is null once config is cached. An empty expected
     * token is a hard 403 so a misconfigured deploy cannot open this up.
     */
    protected function authorized(Request $request): bool
    {
        $expected = (string) config('automation.internal_token', '');
        $provided = (string) $request->header('X-Internal-Token', '');

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }

    /**
     * GET /api/v1/webhooks — list subscriptions. Secrets are never exposed.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => WebhookSubscription::query()
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    /**
     * POST /api/v1/webhooks — create a subscription.
     * Body: { "url": "https://...", "events": ["article.published"] }
     * The signing secret is returned ONLY here, once.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', 'url', 'starts_with:https://'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string', 'in:' . implode(',', WebhookSubscription::ALLOWED_EVENTS) . ',*'],
        ]);

        $subscription = WebhookSubscription::create([
            'url' => $validated['url'],
            'events' => $validated['events'] ?? [WebhookSubscription::EVENT_ARTICLE_PUBLISHED],
            'secret' => bin2hex(random_bytes(32)),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook subscription created. The secret is shown once — store it now; it cannot be retrieved later.',
            'data' => [
                'id' => $subscription->getKey(),
                'url' => $subscription->url,
                'events' => $subscription->events,
                'is_active' => $subscription->is_active,
                'secret' => $subscription->secret,
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/webhooks/{id} — remove a subscription.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $subscription = WebhookSubscription::query()->find($id);

        if (! $subscription) {
            return response()->json(['success' => false, 'message' => 'Webhook subscription not found.'], 404);
        }

        $subscription->delete();

        return response()->json(['success' => true, 'message' => 'Webhook subscription deleted.']);
    }
}
