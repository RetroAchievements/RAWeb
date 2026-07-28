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

        $token = Auth::guard('oauth')->user()?->token();

        if (!$token instanceof ScopeAuthorizable || $token->cant(OAuthScope::Read->value)) {
            throw JsonApiException::error([
                'status' => '403',
                'title' => 'Forbidden',
                'code' => 'missing_scope',
                'detail' => 'This OAuth token does not grant access to read RetroAchievements data.',
            ]);
        }

        return $next($request);
    }
}
