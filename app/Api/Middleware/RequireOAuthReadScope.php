<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Enums\OAuthScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Response;

class RequireOAuthReadScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('api-token-header')->check()) {
            return $next($request);
        }

        /**
         * This gate asks whether a token may read the public tier at all, so it
         * has nothing to say about a route that declares its own scope. Deferring
         * keeps each scope self-sufficient. An app may request exactly the scope
         * matching the data it wants, instead of also carrying the public read
         * scope as a prerequisite for private data.
         */
        if ($this->routeDeclaresItsOwnScope($request)) {
            return $next($request);
        }

        $token = Auth::guard('oauth')->user()?->token();

        if (!$token instanceof ScopeAuthorizable || $token->cant(OAuthScope::Read->value)) {
            /**
             * The scope is carried in meta because a caller-private endpoint
             * can emit "missing_scope" from either this gate or the route-level
             * one, and the detail string is not a contract clients may parse.
             */
            throw JsonApiException::error([
                'status' => '403',
                'title' => 'Forbidden',
                'code' => 'missing_scope',
                'detail' => 'This OAuth token does not grant access to read RetroAchievements data.',
                'meta' => ['required_scope' => OAuthScope::Read->value],
            ]);
        }

        return $next($request);
    }

    private function routeDeclaresItsOwnScope(Request $request): bool
    {
        $routeMiddleware = $request->route()?->gatherMiddleware() ?? [];

        foreach ($routeMiddleware as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, RequireOAuthTokenWithScope::class)) {
                return true;
            }
        }

        return false;
    }
}
