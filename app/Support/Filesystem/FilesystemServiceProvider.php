<?php

declare(strict_types=1);

namespace App\Support\Filesystem;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                /*
                 * LinkStorage
                 */
                LinkStorage::class,
            ]);
        }
    }
}
