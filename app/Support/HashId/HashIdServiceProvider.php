<?php

declare(strict_types=1);

namespace App\Support\HashId;

use Illuminate\Support\ServiceProvider;
use Jenssegers\Optimus\Optimus;

class HashIdServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Optimus::class, fn ($app) => new Optimus(
            (int) config('optimus.prime'),
            (int) config('optimus.inverse'),
            (int) config('optimus.random'),
            (int) config('optimus.bit_length')
        ));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
