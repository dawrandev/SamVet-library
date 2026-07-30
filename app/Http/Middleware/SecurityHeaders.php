<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Baseline security headers for every web response. Content-Security-Policy
     * is deliberately left out — Alpine.js evaluates inline `x-data`/`@click`
     * expressions via Function(), which a strict CSP blocks without either
     * 'unsafe-eval' (defeats the point) or moving to Alpine's CSP-safe build
     * (a real refactor). That's its own, more careful future effort.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Only once the app is actually served over HTTPS in production —
        // announcing HSTS while still reachable over plain HTTP would risk
        // locking out a visitor before the certificate is fully in place.
        if (app()->environment('production') && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
