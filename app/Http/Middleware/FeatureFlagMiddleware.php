<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FeatureFlagMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // EXAMPLE (also add cookie to EncryptCookies.php)
        // =======
        // $cookieValue = $request->cookie('feature_aggregate_queries');

        // // Override the feature flag configuration if the cookie is set to 'true'.
        // if ($cookieValue === 'true') {
        //     config(['feature.aggregate_queries' => true]);
        // } elseif ($cookieValue === 'false') {
        //     config(['feature.aggregate_queries' => false]);
        // }
        // =======

        return $next($request);
    }
}
