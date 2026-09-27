<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureSafeNewsChatRequest
{
    protected const MAX_CONTENT_LENGTH = 51200;

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isMethod('post')) {
            return $this->errorResponse('Invalid request method.', 405);
        }

        if (! $request->isJson()) {
            return $this->errorResponse('Requests must be JSON.', 415);
        }

        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($contentLength > self::MAX_CONTENT_LENGTH) {
            return $this->errorResponse('Request payload is too large.', 413);
        }

        if (($request->header('X-Requested-With') ?? '') !== 'XMLHttpRequest') {
            return $this->errorResponse('Invalid request context.', 403);
        }

        if (! $this->isSameOriginRequest($request)) {
            return $this->errorResponse('Cross-origin request blocked.', 403);
        }

        return $next($request);
    }

    protected function isSameOriginRequest(Request $request): bool
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = $request->getHost();
        $allowedHosts = collect([$appHost, $requestHost])
            ->filter()
            ->map(fn ($host) => strtolower((string) $host))
            ->unique()
            ->values()
            ->all();

        $originHost = $this->extractHost($request->header('Origin'));
        $refererHost = $this->extractHost($request->header('Referer'));

        if (! $originHost && ! $refererHost) {
            return false;
        }

        if ($originHost && ! in_array($originHost, $allowedHosts, true)) {
            return false;
        }

        if ($refererHost && ! in_array($refererHost, $allowedHosts, true)) {
            return false;
        }

        return true;
    }

    protected function extractHost(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host ? strtolower((string) $host) : null;
    }

    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
