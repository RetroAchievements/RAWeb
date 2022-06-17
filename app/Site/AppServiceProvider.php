<?php

declare(strict_types=1);

namespace App\Site;

use App\Site\Commands\CleanupAvatars;
use App\Site\Commands\DeleteUsers;
use App\Site\Commands\SyncUsers;
use App\Site\Commands\SystemAlert;
use App\Site\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
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
    }
}
