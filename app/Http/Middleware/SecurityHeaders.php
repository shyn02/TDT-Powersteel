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

        // Prevent clickjacking - page cannot be framed
        $response->headers->set('X-Frame-Options', 'DENY');
        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // XSS filter for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        // Referrer policy - don't leak full URL on cross-site
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Permissions - block unused browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // HSTS - only over HTTPS, 1 year, include subdomains
        if ($request->isSecure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        // SEC-06: Tightened CSP — removed unsafe-eval, narrowed script sources.
        // unsafe-inline kept for Filament/Livewire (adding nonce would disable unsafe-inline per spec and break Filament).
        // Nonce is generated and shared for app.blade.php future migration; not yet enforced in CSP header.
        // Next step: move inline blocks to Vite/static or add nonce to Filament, then drop unsafe-inline and enforce Report-Only first.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://esm.sh",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com",
            "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://esm.sh https://cdn.jsdelivr.net https://www.google.com",
            "frame-src 'self' https://www.google.com https://maps.google.com https://google.com",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
