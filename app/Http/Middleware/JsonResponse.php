<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JsonResponse
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Don't override the Accept header if it's already set to the media type
        // required by JSON:API responses.
        $currentAccept = $request->header('Accept');
        if ($currentAccept !== 'application/vnd.api+json') {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
