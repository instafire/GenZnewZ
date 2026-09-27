<?php

namespace Botble\ACL\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Throwable;

class Authenticate extends BaseAuthenticate
{
    protected array $guards;

    public function handle($request, Closure $next, ...$guards)
    {
        $this->guards = $guards;

        $this->authenticate($request, $guards);

        if (! $guards) {
            $user = $request->user();

            if ($user && ! $user->activated) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => trans('core/acl::auth.deactivated_message')], 401);
                }

                return redirect()->route('access.login')->with('error_msg', trans('core/acl::auth.deactivated_message'));
            }

            $route = $request->route();
            $flag = $route->getAction('permission');
            if ($flag === null) {
                $flag = $route->getName();
            }

            $flag = preg_replace('/.store$/', '.create', $flag);
            $flag = preg_replace('/.update$/', '.edit', $flag);

            if ($flag && ! $request->user()->hasAnyPermission((array) $flag)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                return redirect()->route('dashboard.index');
            }
        }

        return $next($request);
    }

    protected function redirectTo($request): ?string
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($this->guards) {
            $redirectCallback = apply_filters('cms_unauthenticated_redirect_to', null, $request);

            if ($redirectCallback) {
                return $redirectCallback;
            }

            try {
                return parent::redirectTo($request);
            } catch (Throwable) {
                abort(401);
            }
        }

        return route('access.login');
    }
}
