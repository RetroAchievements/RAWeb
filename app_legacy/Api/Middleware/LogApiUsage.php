<?php

namespace LegacyApp\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use LegacyApp\Site\Models\User;

class LogApiUsage
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user('api-token-legacy');

        $user->timestamps = false;
        $user->increment('APIUses');

        return $next($request);
    }
}
