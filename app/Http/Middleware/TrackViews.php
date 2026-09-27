<?php

namespace App\Http\Middleware;

use App\Jobs\TrackPostViewJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrackViews
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if this is a post view page
        if ($request->route() && $request->route()->getName() === 'public.single') {
            $slug = $request->route('slug');
            
            if ($slug) {
                $ip = $request->ip();
                $cacheKey = "viewed_post_{$slug}_{$ip}";
                
                if (Cache::add($cacheKey, true, now()->addHours(24))) {
                    TrackPostViewJob::dispatchAfterResponse((string) $slug);
                }
            }
        }
        
        return $response;
    }
}
