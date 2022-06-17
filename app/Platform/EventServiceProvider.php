<?php

declare(strict_types=1);

namespace App\Platform;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
    ];

    public function boot(): void
    {
    }
}
