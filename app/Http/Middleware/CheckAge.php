<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAge
{
    public function handle(Request $request, Closure $next): mixed
    {
        /*
         * TODO: check cookie for age gates on content
         * TODO: check if user wants to see mature content - default to no
         */
        return $next($request);
    }
}
