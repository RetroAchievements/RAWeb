<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use Closure;
use Illuminate\Http\Request;

class AccessControlAllowOriginWildcard
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $response->header('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
