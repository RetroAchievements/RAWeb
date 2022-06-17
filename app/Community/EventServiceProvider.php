<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Listeners\WriteUserActivity;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTriggerEdited;
use App\Platform\Events\PlayerCompletedAchievementSet;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Events\PlayerLeaderboardEntrySubmitted;
use App\Platform\Events\PlayerLeaderboardEntryUpdated;
use App\Platform\Events\PlayerSessionResumed;
use App\Platform\Events\PlayerSessionStarted;
use App\Platform\Events\PlayerUnlockedAchievement;
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
        AchievementTriggerEdited::class => [
            WriteUserActivity::class,
        ],
        PlayerLeaderboardEntrySubmitted::class => [
            WriteUserActivity::class,
        ],
        PlayerLeaderboardEntryUpdated::class => [
            WriteUserActivity::class,
        ],
        PlayerUnlockedAchievement::class => [
            WriteUserActivity::class,
        ],
        PlayerCompletedAchievementSet::class => [
            WriteUserActivity::class,
        ],
        PlayerSessionStarted::class => [
        ],
        PlayerSessionResumed::class => [
        ],
        PlayerGameAttached::class => [
            WriteUserActivity::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
