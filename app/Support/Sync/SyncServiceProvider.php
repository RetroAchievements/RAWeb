<?php

declare(strict_types=1);

namespace App\Support\Sync;

use App\Support\Sync\Commands\SyncDisable;
use App\Support\Sync\Commands\SyncEnable;
use App\Support\Sync\Commands\SyncUpdateStatus;
use Illuminate\Support\ServiceProvider;

class SyncServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([

                SyncUpdateStatus::class,

                /*
                 * Settings
                 */
                SyncDisable::class,
                SyncEnable::class,
            ]);
        }
    }
}
