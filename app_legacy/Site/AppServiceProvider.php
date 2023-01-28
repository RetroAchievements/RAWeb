<?php

declare(strict_types=1);

namespace LegacyApp\Site;

use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use LegacyApp\Site\Commands\DeleteExpiredEmailVerificationTokens;
use LegacyApp\Site\Commands\DeleteOverdueUserAccounts;
use LegacyApp\Site\Commands\LogUsersOnlineCount;
use LegacyApp\Site\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LogUsersOnlineCount::class,
                DeleteExpiredEmailVerificationTokens::class,
                DeleteOverdueUserAccounts::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(LogUsersOnlineCount::class)->everyThirtyMinutes();
            $schedule->command(DeleteExpiredEmailVerificationTokens::class)->daily();
            $schedule->command(DeleteOverdueUserAccounts::class)->daily();
        });

        $this->loadMigrationsFrom([database_path('migrations/legacy')]);

        Model::shouldBeStrict(!$this->app->isProduction());

        Relation::morphMap([
            'user' => User::class,
        ]);

        // TODO remove
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
                if (app()->environment('local', 'testing')) {
                    throw $exception;
                } else {
                    echo 'Could not connect to database. Please try again later.';
                    exit;
                }
            }
        });
    }
}
