<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standard security headers on every API response:
 *  - no MIME sniffing, no framing, no referrer leaks, no browser features;
 *  - HSTS when served over HTTPS;
 *  - "no-store" so browsers / proxies never cache private JSON.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // JSON only: file downloads keep their own caching headers.
        if (str_contains((string) $headers->get('Content-Type'), 'json')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        $headers->remove('X-Powered-By');

        return $response;
    }
}
