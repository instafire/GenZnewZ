<?php

namespace Botble\Language\Http\Middleware;

use Botble\Language\Facades\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ApiLanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $languageCode = null;

        if ($request->has('language')) {
            $languageCode = $request->query('language');
        } elseif ($request->header('X-LANGUAGE')) {
            $languageCode = $request->header('X-LANGUAGE');
        }

        if ($languageCode && Language::checkLocaleInSupportedLocales($languageCode)) {
            App::setLocale($languageCode);
            Language::setLocale($languageCode);
        }

        return $next($request);
    }
}
