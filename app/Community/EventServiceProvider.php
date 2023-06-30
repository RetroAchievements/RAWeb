<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Listeners\WriteUserActivity;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementSetCompleted;
use App\Platform\Events\AchievementUpdated;
use App\Platform\Events\LeaderboardEntryCreated;
use App\Platform\Events\LeaderboardEntryUpdated;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Events\PlayerSessionResumed;
use App\Platform\Events\PlayerSessionStarted;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        /*
         * framework
         */
        Login::class => [
            // WriteUserActivity::class,
        ],

        /*
         * Platform Events - Account Listeners
         */
        AchievementCreated::class => [
            WriteUserActivity::class,
        ],
        AchievementPublished::class => [
            WriteUserActivity::class,
        ],
        AchievementSetCompleted::class => [
            WriteUserActivity::class,
        ],
        AchievementUpdated::class => [
            WriteUserActivity::class,
        ],
        LeaderboardEntryCreated::class => [
            WriteUserActivity::class,
        ],
        LeaderboardEntryUpdated::class => [
            WriteUserActivity::class,
        ],
        PlayerAchievementUnlocked::class => [
            WriteUserActivity::class,
        ],
        PlayerGameAttached::class => [
            WriteUserActivity::class,
        ],
        PlayerSessionStarted::class => [
        ],
        PlayerSessionResumed::class => [
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
