<?php

declare(strict_types=1);

namespace App\Site\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     */
    protected $except = [
        'password',
        'password_confirmation',
    ];
}
