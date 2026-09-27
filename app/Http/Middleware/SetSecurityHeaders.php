<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Add HTTPS-focused security headers for SEO and browser hardening.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isHttps = $request->isSecure()
            || strcasecmp((string) $request->headers->get('X-Forwarded-Proto'), 'https') === 0
            || strcasecmp((string) $request->headers->get('Front-End-Https'), 'on') === 0;

        if ($isHttps) {
            // Enable HSTS so browsers always use HTTPS after first secure visit.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP must be emitted from PHP: this stack is nginx -> PHP-FPM, so the
        // .htaccess rules are never processed. Keep this list in sync with every
        // third-party origin embedded by the theme (GTM, GA4, AdSense, Clarity)
        // and the admin panel (Google Fonts). Google Fonts stays allowed for the
        // admin panel; the public theme serves its fonts self-hosted.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://www.googletagmanager.com https://www.google-analytics.com https://pagead2.googlesyndication.com https://www.clarity.ms https://scripts.clarity.ms https://static.cloudflareinsights.com https://www.google.com https://www.googleadservices.com https://googleads.g.doubleclick.net https://adservice.google.com https://fundingchoicesmessages.google.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https: http:",
            "media-src 'self' https:",
            "connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://stats.g.doubleclick.net https://www.google.com https://www.clarity.ms https://*.clarity.ms https://pagead2.googlesyndication.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://fundingchoicesmessages.google.com https://ep1.adtrafficquality.google https://ep2.adtrafficquality.google",
            "frame-src 'self' https://www.googletagmanager.com https://www.google.com https://googleads.g.doubleclick.net https://www.youtube.com https://www.youtube-nocookie.com https://td.doubleclick.net",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]));

        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
