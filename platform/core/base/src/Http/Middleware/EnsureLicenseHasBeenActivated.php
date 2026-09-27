<?php

namespace Botble\Base\Http\Middleware;

use Botble\Base\Supports\Core;
use Closure;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

readonly class EnsureLicenseHasBeenActivated
{
    public function __construct(private Core $core)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (
            ! is_in_admin(true)
            || Auth::guest()
            || $this->core->isSkippedLicenseReminder()
        ) {
            return $next($request);
        }

        try {
            if ($this->core->verifyLicense(true, 15)) {
                return $next($request);
            }
        } catch (Exception $e) {
            // If we can't connect to the license server, allow the request to proceed
            if ($e instanceof ConnectionException) {
                $this->core->skipLicenseReminder();

                return $next($request);
            }

            throw $e;
        }

        $whitelistRoutes = [
            'unlicensed',
            'unlicensed.skip',
            'settings.license.activate',
        ];

        if (! $request->routeIs($whitelistRoutes)) {
            return redirect()->route('unlicensed', ['redirect_url' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
