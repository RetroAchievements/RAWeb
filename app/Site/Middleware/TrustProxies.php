<?php

declare(strict_types=1);

namespace App\Site\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     */
    protected $proxies = [
        // TODO
        '91.141.0.20',
    ];
}
