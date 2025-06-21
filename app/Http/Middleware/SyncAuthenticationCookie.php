<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Synchronizes an 'authenticated' cookie with Laravel's session authentication state.
 *
 * This middleware facilitates Cloudflare edge caching for unauthenticated users by providing
 * a lightweight cookie that Cloudflare can check for presence/absence. If the cookie is
 * absent, the user can receive content from the edge cache rather than doing a direct server hit.
 *
 * Why this is actually needed:
 * - Laravel _always_ sets a session cookie, even for unauthenticated users.
 * - Therefore, Cloudflare can't determine auth state from the presence of the session cookie.
 * - We need a simple boolean indicator that Cloudflare rules can check which is readable before render-time.
 *
 * How it works:
 * - Authenticated users get an 'authenticated=1' cookie.
 * - Unauthenticated users have no 'authenticated' cookie.
 * - Cloudflare caches pages when 'authenticated' cookie is absent.
 */
class SyncAuthenticationCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            // User is authenticated, so set the 'authenticated' cookie.
            $response->withCookie(
                cookie(
                    'authenticated',
                    '1',
                    config('session.lifetime'),
                    config('session.path'),
                    config('session.domain'),
                    config('session.secure'),
                    true, // httpOnly
                    false, // raw
                    config('session.same_site')
                )
            );
        } else {
            // User is not authenticated, so remove the 'authenticated' cookie if it exists.
            if ($request->hasCookie('authenticated')) {
                $response->withCookie(
                    cookie()->forget('authenticated', config('session.path'), config('session.domain'))
                );
            }
        }

        return $response;
    }
}
