<?php

namespace Botble\Shortcode\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ShortcodePerformanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! App::hasDebugModeEnabled()) {
            return $response;
        }

        $startTime = microtime(true);

        $executionTime = microtime(true) - $startTime;

        $threshold = 0.5; // Default threshold of 500ms

        if ($executionTime > $threshold) {
            Log::warning(sprintf(
                'Shortcode render-ui-blocks took %s seconds. Shortcode: %s, Attributes: %s',
                number_format($executionTime, 4),
                $request->input('name'),
                json_encode($request->input('attributes', []))
            ));
        }

        $response->headers->set('X-Shortcode-Execution-Time', number_format($executionTime, 4));

        return $response;
    }
}
