<?php

declare(strict_types=1);

namespace App\Console;

use App\Support\Settings\Settings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('cache:prune-stale-tags')->hourly();

        // $schedule->command('websockets:clean')->daily();

        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('queue:prune-batches --hours=48 --unfinished=72 --cancelled=72')->daily();

        /** @var Settings $settings */
        $settings = $this->app->get(Settings::class);

        // sync
        $syncEnabled = $settings->get('sync', true);
        if ($syncEnabled) {
            /*
             * sync incrementally every minute
             * check the state of each kind first
             */
            $schedule->command('ra:sync:status')
                ->everyMinute()
                ->onOneServer()
                ->runInBackground()
                ->withoutOverlapping()
                ->then(function () {
                    // Site
                    // $this->call('ra:sync:users');

                    // Platform
                    // $this->call('ra:sync:systems');
                    // $this->call('ra:sync:games');
                    // $this->call('ra:sync:achievements');
                    // $this->call('ra:sync:game-hashes');
                    // $this->call('ra:sync:memory-notes');

                    // $this->call('ra:sync:user-awards');
                    // $this->call('ra:sync:game-relations');
                    // $this->call('ra:sync:leaderboards');
                    $this->call('ra:sync:leaderboard-entries');

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
}
