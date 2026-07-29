<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards caller-private endpoints, where a scoped and revocable OAuth grant is
 * the only acceptable credential. Legacy API keys are full-access and never
 * expire, so they are refused here even though they are accepted everywhere
 * else in the V2 API.
 */
class RequireOAuthTokenWithScope
{
    public function handle(Request $request, Closure $next, string $requiredScope): Response
    {
        if (Auth::guard('api-token-header')->check()) {
            if (app()->environment('local') && !config('feature.oauth')) {
                return $next($request);
            }

            throw JsonApiException::error([
                'status' => '403',
                'title' => 'Forbidden',
                'code' => 'oauth_required',
                'detail' => 'This endpoint requires an OAuth token. API keys cannot be used to read caller-private data.',
                'meta' => ['required_scope' => $requiredScope],
            ]);
        }

        $token = Auth::guard('oauth')->user()?->token();

        if (!$token instanceof ScopeAuthorizable || $token->cant($requiredScope)) {
            throw JsonApiException::error([
                'status' => '403',
                'title' => 'Forbidden',
                'code' => 'missing_scope',
                'detail' => "This OAuth token does not grant the \"{$requiredScope}\" scope.",
                'meta' => ['required_scope' => $requiredScope],
            ]);
        }

        return $next($request);
    }
}
