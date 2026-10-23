<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Portal and dashboard pages hold personal health information: never keep them in a shared cache or the back/forward cache.
        if ($request->is('results', 'results/*', 'admin', 'admin/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if (woodlands('preview')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
