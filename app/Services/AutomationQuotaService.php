<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Shared vocabulary for the automation API rate limits.
 *
 * The named limiters are registered in AppServiceProvider and applied to the
 * routes in ThemeServiceProvider. Both the middleware and GET /me need to agree
 * on the cache key a quota is counted under, so the key format lives here in one
 * place instead of being built twice and drifting.
 *
 * Keys are derived from a sha1 of the API token rather than the token itself:
 * the production cache store is the file driver, which puts part of the key in a
 * filename, and a raw credential does not belong there.
 */
class AutomationQuotaService
{
    public const REGISTER = 'automation-register';

    public const AUTH = 'automation-auth';

    public const RECOVER = 'automation-recover';

    public const READ = 'automation-read';

    public const PUBLISH = 'automation-publish';

    /**
     * The bucket a quota is counted against: the reporter's token when we have
     * one, otherwise the caller's IP.
     */
    public function subject(Request $request): string
    {
        $token = (string) ($request->header('X-API-Token') ?: $request->input('api_token', ''));

        if ($token !== '') {
            return 'token:' . substr(sha1($token), 0, 16);
        }

        // The site sits behind Cloudflare, so $request->ip() is a Cloudflare
        // edge node that changes from request to request and is useless as a
        // throttle key. CF-Connecting-IP is set by Cloudflare itself to the
        // real client IP and cannot be spoofed through it.
        $ip = $request->header('CF-Connecting-IP') ?: $request->ip();

        return 'ip:' . ($ip ?: 'unknown');
    }

    /**
     * The cache key the throttle middleware counts this caller under.
     */
    public function key(string $limiter, Request $request): string
    {
        return $limiter . ':' . $this->subject($request);
    }

    public function limit(string $limiter): int
    {
        $limits = (array) config('automation.rate_limits', []);

        return (int) match ($limiter) {
            self::REGISTER => $limits['registrations_per_hour'] ?? 10,
            self::AUTH => $limits['auth_per_minute'] ?? 20,
            self::RECOVER => $limits['recoveries_per_hour'] ?? 5,
            self::READ => $limits['reads_per_minute'] ?? 120,
            self::PUBLISH => $limits['publishes_per_hour'] ?? 50,
            default => 0,
        };
    }

    public function window(string $limiter): string
    {
        return in_array($limiter, [self::PUBLISH, self::REGISTER, self::RECOVER], true) ? 'hour' : 'minute';
    }

    /**
     * Current usage for one limiter, as reported to the caller.
     *
     * @return array<string, mixed>
     */
    public function usage(string $limiter, Request $request): array
    {
        $limit = $this->limit($limiter);
        $key = $this->key($limiter, $request);

        return [
            'limit' => $limit,
            'window' => $this->window($limiter),
            'used' => RateLimiter::attempts($key),
            'remaining' => RateLimiter::remaining($key, $limit),
            'reset_in_seconds' => RateLimiter::availableIn($key),
        ];
    }

    /**
     * Everything a reporter can see about their own quota.
     *
     * @return array<string, mixed>
     */
    public function summary(Request $request): array
    {
        return [
            'publishes' => $this->usage(self::PUBLISH, $request),
            'reads' => $this->usage(self::READ, $request),
            'auth' => $this->usage(self::AUTH, $request),
            'headers' => 'X-RateLimit-Limit and X-RateLimit-Remaining are sent on every response; Retry-After and X-RateLimit-Reset are added when a limit is hit.',
        ];
    }
}
