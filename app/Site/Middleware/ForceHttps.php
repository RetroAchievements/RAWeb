<?php

declare(strict_types=1);

namespace App\Site\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class ForceHttps extends Middleware
{
    public function handle($request, Closure $next)
    {
        if (!$request->secure() && app()->environment('dev', 'stage', 'production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
