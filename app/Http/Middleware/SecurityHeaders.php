<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response headers that cost nothing and close off whole categories of
 * mistake. Deliberately no Content-Security-Policy: the layouts use inline
 * scripts (the theme flash-guard) and Alpine needs unsafe-eval, so a CSP
 * added without care would break the site while looking like it worked.
 * That one wants doing properly, with nonces, as its own piece of work.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Stop the browser second-guessing declared content types — the route
        // that serves profile photos is the one that would suffer.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Nothing here is meant to be framed, so refuse clickjacking outright.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Claim-invite tokens travel in the URL path. Without this, following
        // any outbound link from a claim page would hand that token to the
        // other site in the Referer header.
        $response->headers->set('Referrer-Policy', 'same-origin');

        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        // Only over HTTPS, and only in production: sending HSTS from a local
        // http://localhost would pin the whole of localhost to HTTPS in your
        // browser, which is a memorable afternoon to lose. No includeSubDomains
        // or preload — both are far harder to walk back than they look.
        if ($request->secure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
