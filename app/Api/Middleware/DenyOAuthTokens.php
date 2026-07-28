<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Response;

class DenyOAuthTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('oauth')->check()) {
            throw JsonApiException::error([
                'status' => '403',
                'title' => 'Forbidden',
                'code' => 'missing_scope',
                'detail' => 'This OAuth token does not grant access to private RetroAchievements data.',
            ]);
        }

        return $next($request);
    }
}
