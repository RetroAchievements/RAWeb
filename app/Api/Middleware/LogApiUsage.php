<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Site\Models\User;
use Closure;
use Illuminate\Http\Request;

class LogApiUsage
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user('api-token');

        $user->increment('APIUses');

        return $next($request);
    }
}
