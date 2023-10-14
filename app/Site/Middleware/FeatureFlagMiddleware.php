<?php

declare(strict_types=1);

namespace App\Site\Middleware;

use Closure;
use Illuminate\Http\Request;

class FeatureFlagMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Check for the presence of the 'aggregate_queries' cookie.
        $cookieValue = $request->cookie('feature_aggregate_queries');

        // Override the feature flag configuration if the cookie is set to 'true'
        if ($cookieValue === 'true') {
            config(['feature.aggregate_queries' => true]);
        } elseif ($cookieValue === 'false') {
            config(['feature.aggregate_queries' => false]);
        }

        return $next($request);
    }
}
