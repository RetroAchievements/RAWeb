<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // RFC 8058 one-click unsubscribe POSTs come from
        // email client proxies and can't carry a CSRF token.
        'unsubscribe/*',
    ];
}
