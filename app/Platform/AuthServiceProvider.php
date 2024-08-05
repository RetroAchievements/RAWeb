<?php

declare(strict_types=1);

namespace App\Platform;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Auth::extend('connect-token', function ($app, $name, array $config) {
        //     return new TokenGuard(Auth::createUserProvider($config['provider']), $app->request, 't', 'connect_token');
        // });
    }
}
