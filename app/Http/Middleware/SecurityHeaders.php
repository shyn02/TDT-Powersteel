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
        // SEC-06: CSP — unsafe-eval is REQUIRED for Filament/Livewire/Alpine (eval of x-data expressions).
        // Removing it breaks admin completely (Alpine Expression Error). Audit's "remove unsafe-eval" is deferred until Filament no longer needs it.
        // Tightened: narrowed script sources to actually used origins, kept unsafe-inline + unsafe-eval for Filament.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://esm.sh",
            // Filament's FileUpload spins up a Web Worker from a blob: URL (client-side
            // image resizing/thumbnail generation before upload). Without this, the
            // browser falls back to script-src for workers, which doesn't allow blob:,
            // so the worker silently fails to start and image previews never render —
            // the file still uploads/saves fine, only the in-browser preview breaks.
            "worker-src 'self' blob:",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com",
            "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob: https://api.qrserver.com",
            "connect-src 'self' blob: data: http: https: ws: wss: https://fonts.googleapis.com https://fonts.gstatic.com https://esm.sh https://cdn.jsdelivr.net https://www.google.com",
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