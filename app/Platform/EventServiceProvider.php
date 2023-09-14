<?php

declare(strict_types=1);

namespace App\Platform;

use App\Platform\Listeners\ResetPlayerProgress;
use App\Site\Events\UserDeleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserDeleted::class => [
            ResetPlayerProgress::class,
        ],
    ];

    public function boot(): void
    {
    }
}
