<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JsonResponse
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Don't override the Accept header for JSON:API routes.
        // JSON:API requires Accept: application/vnd.api+json
        if (!str_starts_with($request->path(), 'api/v2/') && !str_starts_with($request->path(), 'v2/')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
