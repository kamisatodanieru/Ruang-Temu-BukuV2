<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Tambahkan security headers dasar yang aman untuk aplikasi web.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://app.sandbox.midtrans.com https://app.midtrans.com https://api.sandbox.midtrans.com https://api.midtrans.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "frame-src 'self' https://app.sandbox.midtrans.com https://app.midtrans.com",
            "connect-src 'self' https://app.sandbox.midtrans.com https://app.midtrans.com https://api.sandbox.midtrans.com https://api.midtrans.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Pastikan seluruh cookie (termasuk XSRF-TOKEN) memiliki flag HttpOnly dan Secure pada koneksi HTTPS
        foreach ($response->headers->getCookies() as $cookie) {
            $updatedCookie = $cookie;
            if (! $updatedCookie->isHttpOnly()) {
                $updatedCookie = $updatedCookie->withHttpOnly(true);
            }
            if ($request->isSecure() && ! $updatedCookie->isSecure()) {
                $updatedCookie = $updatedCookie->withSecure(true);
            }
            if ($updatedCookie !== $cookie) {
                $response->headers->setCookie($updatedCookie);
            }
        }

        // Kosongkan body pada respons redirect (3xx) untuk mencegah alert Big Redirect Detected
        if ($response->isRedirection()) {
            $response->setContent('');
        }

        return $response;
    }
}
