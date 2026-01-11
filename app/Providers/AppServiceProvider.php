<?php

declare(strict_types=1);

namespace App\Providers;

use App\Components\GeneralNotificationsIcon;
use App\Components\TicketNotificationsIcon;
use App\Console\Commands\CacheMostPopularEmulators;
use App\Console\Commands\CacheMostPopularSystems;
use App\Console\Commands\CleanupAvatars;
use App\Console\Commands\DeleteExpiredEmailVerificationTokens;
use App\Console\Commands\DeleteExpiredPasswordResetTokens;
use App\Console\Commands\DeleteOverdueUserAccounts;
use App\Console\Commands\FlushUserActivityToDatabase;
use App\Console\Commands\GenerateTypeScript;
use App\Console\Commands\LogUsersOnlineCount;
use App\Console\Commands\PruneApiLogs;
use App\Console\Commands\SquashMigrations;
use App\Console\Commands\SystemAlert;
use App\Http\InertiaResponseFactory;
use App\Models\Comment;
use App\Models\ForumTopicComment;
use App\Models\Message;
use App\Models\News;
use App\Models\Role;
use App\Models\User;
use App\Platform\Services\UserLastActivityService;
use EragLaravelDisposableEmail\Rules\DisposableEmailRule;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Inertia\ResponseFactory;
use Jenssegers\Optimus\Optimus;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override Inertia's ResponseFactory to use our custom factory that strips nulls.
        // This can eliminate unnecessary props and speed up hydration.
        $this->app->singleton(ResponseFactory::class, InertiaResponseFactory::class);

        // Track user recent activity timestamps in Redis and flush them periodically to the DB.
        // This keeps the users table indexes from constantly rebalancing 24/7.
        $this->app->singleton(UserLastActivityService::class);

        // Register Optimus for ID obfuscation. Required for spatie/laravel-medialibrary paths.
        $this->app->singleton(Optimus::class, function () {
            return new Optimus(
                (int) config('optimus.prime'),
                (int) config('optimus.inverse'),
                (int) config('optimus.random'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheMostPopularEmulators::class,
                CacheMostPopularSystems::class,
                DeleteExpiredEmailVerificationTokens::class,
                DeleteExpiredPasswordResetTokens::class,
                DeleteOverdueUserAccounts::class,
                FlushUserActivityToDatabase::class,
                GenerateTypeScript::class,
                LogUsersOnlineCount::class,
                PruneApiLogs::class,
                SquashMigrations::class,

                // User Accounts
                CleanupAvatars::class,

                // Settings
                SystemAlert::class,
            ]);
        }

        Model::shouldBeStrict(!$this->app->isProduction());

        // Filament v4: Preserve v3 behavior for layout components spanning full width.
        \Filament\Schemas\Components\Fieldset::configureUsing(fn (\Filament\Schemas\Components\Fieldset $fieldset) => $fieldset
            ->columnSpanFull());
        \Filament\Schemas\Components\Grid::configureUsing(fn (\Filament\Schemas\Components\Grid $grid) => $grid
            ->columnSpanFull());
        \Filament\Schemas\Components\Section::configureUsing(fn (\Filament\Schemas\Components\Section $section) => $section
            ->columnSpanFull());

        // Filament v4: Preserve v3 behavior for unique() validation not ignoring current record by default.
        \Filament\Forms\Components\Field::configureUsing(fn (\Filament\Forms\Components\Field $field) => $field
            ->uniqueValidationIgnoresRecordByDefault(false));

        Pulse::user(fn (User $user) => [
            'name' => $user->username,
            'avatar' => $user->avatarUrl,
        ]);

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(PruneApiLogs::class)->dailyAt('9:00'); // ~ 4:00AM US Eastern
            $schedule->command(LogUsersOnlineCount::class)->everyThirtyMinutes();
            $schedule->command(FlushUserActivityToDatabase::class)->everyMinute()->withoutOverlapping();

            if (app()->environment() === 'production') {
                $schedule->command(DeleteExpiredEmailVerificationTokens::class)->daily();
                $schedule->command(DeleteExpiredPasswordResetTokens::class)->daily();
                $schedule->command(DeleteOverdueUserAccounts::class)->daily();

                $schedule->command(CacheMostPopularEmulators::class)->weeklyOn(4, '8:00'); // Thursdays, ~3:00AM US Eastern
                $schedule->command(CacheMostPopularSystems::class)->weeklyOn(4, '8:30'); // Thursdays, ~3:30AM US Eastern
            }
        });

        /**
         * Register an alias for the "disposable_email" rule.
         * We'll set it to "not_disposable_email", which is much more intuitive.
         */
        Validator::extend('not_disposable_email', function ($attribute, $value, $parameters, $validator) {
            $rule = new DisposableEmailRule();

            $error = null;
            $failCallback = function (string $message) use (&$error): PotentiallyTranslatedString {
                $error = $message;

                return new PotentiallyTranslatedString($message, app('translator'));
            };

            $rule->validate($attribute, $value, $failCallback);

            return empty($error);
        }, __('validation.not_disposable_email'));

        // TODO remove in favor of Inertia+React components
        Blade::if('hasfeature', function ($feature) {
            return config("feature.$feature", false);
        });

        /*
         * https://josephsilber.com/posts/2018/07/02/eloquent-polymorphic-relations-morph-map
         */
        Relation::morphMap([
            // ModerationReportableType values
            'Comment' => Comment::class,
            'DirectMessage' => Message::class,
            'ForumTopicComment' => ForumTopicComment::class,

            'news' => News::class,
            'site_release_note' => News::class,
            'role' => Role::class,
            'user' => User::class,
        ]);

        /*
         * Register Support Livewire components
         */
        // TODO remove in favor of Inertia+React components
        Livewire::component('general-notifications-icon', GeneralNotificationsIcon::class);
        Livewire::component('ticket-notifications-icon', TicketNotificationsIcon::class);

        /*
         * Apply domain namespaces to tests' class name resolvers
         */
        Factory::guessFactoryNamesUsing(fn ($modelName) => 'Database\\Factories\\' . class_basename($modelName) . 'Factory');
        Factory::guessModelNamesUsing(function ($factory) {
            $factoryBasename = Str::replaceLast('Factory', '', class_basename($factory));

            return 'App\\Models\\' . $factoryBasename;
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
                mysqli_set_charset($db, config('database.connections.mysql.charset'));
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
