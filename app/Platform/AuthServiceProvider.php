<?php

declare(strict_types=1);

namespace App\Platform;

use App\Auth\HeaderTokenGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Auth::extend('connect-token', function ($app, $name, array $config) {
        //     return new TokenGuard(Auth::createUserProvider($config['provider']), $app->request, 't', 'connect_token');
        // });

        Auth::extend('header-token', function ($app, $name, array $config) {
            return new HeaderTokenGuard(
                Auth::createUserProvider($config['provider']),
                $app->request,
                $config['input_key'] ?? 'api_key',
                $config['storage_key'] ?? 'api_token',
                $config['hash'] ?? false,
                $config['header_name'] ?? 'X-API-Key'
            );
        });
    }
}
