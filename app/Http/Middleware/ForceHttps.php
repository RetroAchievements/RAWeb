<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class ForceHttps extends Middleware
{
    public function handle($request, Closure $next)
    {
        // TODO enable HSTS on the production server and get rid of this middleware
        if (!$request->secure() && app()->environment('stage', 'production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
