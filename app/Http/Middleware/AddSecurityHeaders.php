<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    private static array $imageOrigins = [];

    // Images can be stored as external URLs, so the policy allows the origins of those the page renders.
    public static function allowImage(string $url): string
    {
        $parts = parse_url($url);

        if (in_array($parts['scheme'] ?? null, ['http', 'https'], true) && preg_match('/^[a-z0-9.-]+$/i', $parts['host'] ?? '')) {
            self::$imageOrigins[] = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        }

        return $url;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();
        self::$imageOrigins = [];

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
        if (! $response->headers->has('Content-Security-Policy') && ! Vite::isRunningHot() && ! (config('app.debug') && ($response->exception ?? null))) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'unsafe-inline' https://rsms.me",
                "font-src 'self' data: https://rsms.me",
                implode(' ', ["img-src 'self' data:", ...array_diff(array_unique(self::$imageOrigins), [$request->getSchemeAndHttpHost()])]),
                "manifest-src 'self'",
                "worker-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]));
        }

        return $response;
    }
}
