<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        header_remove('X-Powered-By');

        if ($response->isRedirection()) {
            $response->setContent('');
        }

        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Embedder-Policy' => 'credentialless',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ]);

        // The Vite dev server and Laravel's debug error page load scripts the policy would block.
        if (! Vite::isRunningHot() && ! (config('app.debug') && ($response->exception ?? null))) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'unsafe-inline' https://rsms.me",
                "font-src 'self' data: https://rsms.me",
                "img-src 'self' data: https:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]));
        }

        return $response;
    }
}
