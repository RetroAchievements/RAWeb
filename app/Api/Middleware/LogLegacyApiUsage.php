<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Sentry\Laravel\Integration;

class LogLegacyApiUsage
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user('api-token');

        DB::transaction(fn () => $user->increment('web_api_calls'), attempts: 3);

        // Override Sentry's parameterized transaction name so each
        // endpoint is individually visible instead of all being grouped
        // under "/API/{method}.php".
        $method = $request->route('method');
        if ($method) {
            Integration::setTransaction("/API/{$method}.php");
        }

        return $next($request);
    }
}
