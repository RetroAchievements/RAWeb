<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Sentry\State\Scope;

use function Sentry\configureScope;

class LogLegacyApiUsage
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user('api-token');

        DB::transaction(fn () => $user->increment('web_api_calls'), attempts: 3);

        // Tag the method so Sentry can group by endpoint. Using setTag
        // instead of setTransaction because Sentry's Laravel integration
        // overwrites the transaction name from the route at end-of-request.
        $method = $request->route('method');
        configureScope(function (Scope $scope) use ($method) {
            $scope->setTag('api.v1.method', $method ?? 'unknown');
        });

        return $next($request);
    }
}
