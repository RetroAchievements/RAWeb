<?php

declare(strict_types=1);

namespace App\Site;

use App\Site\Commands\CleanupAvatars;
use App\Site\Commands\DeleteExpiredEmailVerificationTokens;
use App\Site\Commands\DeleteOverdueUserAccounts;
use App\Site\Commands\DeleteUsers;
use App\Site\Commands\LogUsersOnlineCount;
use App\Site\Commands\SyncUsers;
use App\Site\Commands\SystemAlert;
use App\Site\Commands\UpdateUserTimestamps;
use App\Site\Models\User;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LogUsersOnlineCount::class,
                DeleteExpiredEmailVerificationTokens::class,
                DeleteOverdueUserAccounts::class,
                UpdateUserTimestamps::class,

                /*
                 * User Accounts
                 */
                CleanupAvatars::class,
                DeleteUsers::class,
                SyncUsers::class,

                /*
                 * Settings
                 */
                SystemAlert::class,
            ]);
        }

        Model::shouldBeStrict(!$this->app->isProduction());

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(LogUsersOnlineCount::class)->everyThirtyMinutes();
            $schedule->command(DeleteExpiredEmailVerificationTokens::class)->daily();
            $schedule->command(DeleteOverdueUserAccounts::class)->daily();
        });

        /*
         * https://josephsilber.com/posts/2018/07/02/eloquent-polymorphic-relations-morph-map
         */
        Relation::morphMap([
            'user' => User::class,
        ]);

        /*
         * Register Support Livewire components
         */
        // Livewire::component('grid', Grid::class);

        /*
         * Register Livewire components
         */
        // Livewire::component('supersearch', Supersearch::class);
        // Livewire::component('user-grid', UserGrid::class);

        /*
         * Apply domain namespaces to tests' class name resolvers
         */
        Factory::guessFactoryNamesUsing(fn ($modelName) => 'Database\\Factories\\' . class_basename($modelName) . 'Factory');
        Factory::guessModelNamesUsing(function ($factory) {
            $factoryBasename = Str::replaceLast('Factory', '', class_basename($factory));

            if (class_exists('App\\Community\\Models\\' . $factoryBasename)) {
                return 'App\\Community\\Models\\' . $factoryBasename;
            }
            if (class_exists('App\\Platform\\Models\\' . $factoryBasename)) {
                return 'App\\Platform\\Models\\' . $factoryBasename;
            }
            if (class_exists('App\\Site\\Models\\' . $factoryBasename)) {
                return 'App\\Site\\Models\\' . $factoryBasename;
            }

            return class_exists('App\\Models\\' . $factoryBasename)
                ? 'App\\Models\\' . $factoryBasename
                : 'App\\' . $factoryBasename;
        });

        // TODO remove
        $this->app->singleton('mysqli', function () {
            try {
                $db = mysqli_connect(
                    config('database.connections.mysql.host'),
                    config('database.connections.mysql.username'),
                    config('database.connections.mysql.password'),
                    config('database.connections.mysql.database'),
                    (int) config('database.connections.mysql.port')
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
                }
                echo 'Could not connect to database. Please try again later.';
                exit;
            }
        });
    }
}
