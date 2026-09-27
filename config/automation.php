<?php

return [
    /*
     * Internal token that authorises the live-events refresh endpoint
     * (POST /api/live-events/refresh, header X-Internal-Token).
     *
     * Read through config() and never env() at the call site: env() returns null
     * once `php artisan config:cache` has run, and the controller treats an empty
     * expected token as a hard 403. A cached production deploy would therefore
     * have silently broken this endpoint forever.
     */
    'internal_token' => env('AI_AUTOMATION_TOKEN'),

    /*
     * Minimum number of seconds between two accepted cache-refresh triggers
     * (POST /api/live-events/refresh). The endpoint is reachable without a token —
     * a browser cannot hold a secret without publishing it — so this bounds how
     * often a caller can ask for a refresh. Set to 0 to disable the cooldown.
     */
    'refresh_cooldown_seconds' => (int) env('AI_AUTOMATION_REFRESH_COOLDOWN', 60),

    /*
     * Rate limits for the public automation API.
     *
     * GET /api/v1/automation/status used to advertise "rate_limiting: true" and
     * "max_posts_per_hour: 50" while nothing was enforced anywhere in the
     * application - the routes carried no throttle middleware and the controller
     * never called the rate limiter. These values are the real ones: they are
     * registered as named limiters and applied to the routes, and /status reports
     * them from this file so the promise cannot drift from the behaviour again.
     *
     * Publishes and reads are counted per reporter (by API token) so one agent
     * cannot exhaust another's quota; registration and login are counted per IP
     * because no token exists yet.
     */
    'rate_limits' => [
        // New accounts per IP per hour. Registration is the only unauthenticated
        // way to create rows, so it is the tightest limit.
        'registrations_per_hour' => (int) env('AUTOMATION_RATE_REGISTRATIONS_PER_HOUR', 10),

        // Login and token rotation per IP per minute.
        'auth_per_minute' => (int) env('AUTOMATION_RATE_AUTH_PER_MINUTE', 20),

        // Read endpoints (categories, posts, opportunities, SEO validation).
        'reads_per_minute' => (int) env('AUTOMATION_RATE_READS_PER_MINUTE', 120),

        // Posts created or updated per reporter per hour.
        'publishes_per_hour' => (int) env('AUTOMATION_RATE_PUBLISHES_PER_HOUR', 50),
    ],

    /*
     * Idempotency keys for POST /posts/create.
     *
     * An agent whose request times out cannot tell whether the article was
     * published or not, so the safe retry is to repeat the request. Without a key
     * that creates a second article; with one, the retry returns the first. The
     * key is stored on the post itself as well as in the cache, so a replay still
     * works after a cache flush.
     */
    'idempotency' => [
        'enabled' => (bool) env('AUTOMATION_IDEMPOTENCY_ENABLED', true),
        'min_length' => 8,
        'max_length' => 255,

        // How long a key protects against a duplicate retry.
        'ttl_hours' => (int) env('AUTOMATION_IDEMPOTENCY_TTL_HOURS', 48),
    ],

    /*
     * Caching for GET /opportunities (the newsroom briefing).
     */
    'opportunities' => [
        'cache_minutes' => (int) env('AUTOMATION_OPPORTUNITIES_CACHE_MINUTES', 10),
    ],
];
