<?php

namespace Botble\ACL\Http\Controllers\Auth;

use Botble\ACL\Forms\Auth\LoginForm;
use Botble\ACL\Http\Requests\LoginRequest;
use Botble\ACL\Traits\AuthenticatesUsers;
use Botble\Base\Http\Controllers\BaseController;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Validation\ValidationException;

class LoginController extends BaseController
{
    use AuthenticatesUsers;

    protected string $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);

        $this->redirectTo = route('dashboard.index');
    }

    public function showLoginForm()
    {
        $this->pageTitle(trans('core/acl::auth.login_title'));

        return LoginForm::create()->renderForm();
    }

    public function login(LoginRequest $request)
    {
        $request->merge([$this->username() => $request->input('username')]);

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        return app(Pipeline::class)->send($request)
            ->through(apply_filters('core_acl_login_pipeline', [
                function (Request $request, Closure $next) {
                    $credentials = $this->credentials($request);

                    $callbacks = apply_filters('core_acl_login_attempt_callbacks', [
                        function (Authenticatable $user) {
                            if (! $user->activated) {
                                throw ValidationException::withMessages([
                                    'username' => [trans('core/acl::auth.login.not_active')],
                                ]);
                            }

                            return true;
                        },
                    ]);

                    if ($this->guard()->attemptWhen($credentials, $callbacks, $request->filled('remember'))) {
                        return $next($request);
                    }

                    $this->incrementLoginAttempts($request);

                    return $this->sendFailedLoginResponse();
                },
            ]))
            ->then(function (Request $request) {
                if (! session()->has('url.intended')) {
                    session()->flash('url.intended', url()->current());
                }

                return $this->sendLoginResponse($request);
            });
    }

    public function username(): string
    {
        return filter_var(request()->input('username'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    public function logout(Request $request)
    {
        do_action(AUTH_ACTION_AFTER_LOGOUT_SYSTEM, $request, $request->user());

        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->httpResponse()
            ->setNextRoute('access.login')
            ->setMessage(trans('core/acl::auth.login.logout_success'));
    }
}
