<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Models\User;
use App\Platform\Services\UserApiCallCountService;
use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;

use function Sentry\configureScope;

class LogLegacyApiUsage
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user('api-token');

        app(UserApiCallCountService::class)->record($user);

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
