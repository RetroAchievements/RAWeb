<?php

declare(strict_types=1);

namespace App\Console;

use App\Support\Settings\Settings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [

    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('db:monitor --max=100');

        /*
         * Legacy
         */
        $schedule->call(function () {
            require_once base_path('cronjobs/cron_1m.php');
        })->everyMinute();

        $schedule->call(function () {
            require_once base_path('cronjobs/cron_30m.php');
        })->everyThirtyMinutes();

        $schedule->call(function () {
            require_once base_path('cronjobs/cron_hourly.php');
        })->hourly();

        $schedule->call(function () {
            require_once base_path('cronjobs/cron_daily.php');
        })->daily();

        /*
         * websockets
         */
        // $schedule->command('websockets:clean')->daily();

        /*
         * horizon
         */
        // $schedule->command('horizon:snapshot')->everyFiveMinutes();

        /*
         * stats
         * TODO: currently online stat every 30 mins
         */

        /*
         * TODO: update player ranks / metrics
         */
        // $schedule->command('ra:action:update-user-ranks')
        //          ->everyMinute();

        // $schedule->command('ra:moderate:watchdog')
        //     ->onOneServer()->withoutOverlapping()
        //     ->everyFiveMinutes();

        /** @var Settings $settings */
        $settings = $this->app->get(Settings::class);

        /**
         * sync
         */
        $syncEnabled = $settings->get('sync', true);
        if ($syncEnabled && $this->app->environment('production')) {
            /*
             * sync incrementally every minute
             */
            $schedule->command('ra:sync:status')
                ->everyMinute()
                ->onOneServer()
                ->runInBackground()
                ->withoutOverlapping()
                ->then(function () {
                    // Site
                    // $this->call('ra:sync:users');

                    // Server
                    // $this->call('ra:sync:systems');
                    // $this->call('ra:sync:games');
                    // $this->call('ra:sync:achievements');
                    // $this->call('ra:sync:game-hashes');
                    // $this->call('ra:sync:memory-notes');
                    // $this->call('ra:sync:player-achievements');
                    // $this->call('ra:sync:user-awards');
                    // $this->call('ra:sync:game-relations');
                    // $this->call('ra:sync:leaderboards');
                    // $this->call('ra:sync:leaderboard-entries');

                    // Community
                    // $this->call('ra:sync:forum-categories');
                    // $this->call('ra:sync:forums');
                    // $this->call('ra:sync:forum-topics');
                    // $this->call('ra:sync:news');
                    // $this->call('ra:sync:comments');
                    // $this->call('ra:sync:user-relations');
                    // $this->call('ra:sync:messages');
                    // $this->call('ra:sync:ratings');
                    // $this->call('ra:sync:tickets');
                    // $this->call('ra:sync:votes');
                });
        }
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        // require base_path('routes/console.php');
    }
}
