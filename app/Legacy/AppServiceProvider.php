<?php

declare(strict_types=1);

namespace App\Legacy;

use Exception;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
            ]);
        }

        $this->loadMigrationsFrom([database_path('migrations/legacy')]);

        $this->app->singleton('mysqli', function () {
            try {
                $db = mysqli_connect(
                    config('database.connections.mysql_legacy.host'),
                    config('database.connections.mysql_legacy.username'),
                    config('database.connections.mysql_legacy.password'),
                    config('database.connections.mysql_legacy.database'),
                    (int) config('database.connections.mysql_legacy.port')
                );
                if (!$db) {
                    throw new Exception('Could not connect to database. Please try again later.');
                }
                mysqli_set_charset($db, 'latin1');
                mysqli_query($db, "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

                return $db;
            } catch (Exception $exception) {
                if (app()->environment('local')) {
                    throw $exception;
                } else {
                    echo 'Could not connect to database. Please try again later.';
                    exit;
                }
            }
        });
    }
}
