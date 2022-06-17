<?php

namespace App\Legacy\Middleware;

use App\Legacy\Models\User;
use Closure;
use Illuminate\Http\Request;

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
