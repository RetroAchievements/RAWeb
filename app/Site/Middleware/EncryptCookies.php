<?php

declare(strict_types=1);

namespace App\Site\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     */
    protected $except = [
        'scheme', // dark/light mode
        'theme', // color scheme
        'logo',
        'prefers_hidden_user_completed_sets',
    ];
}
