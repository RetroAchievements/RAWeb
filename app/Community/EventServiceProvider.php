<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Events\MessageCreated;
use App\Community\Listeners\NotifyMessageThreadParticipants;
use App\Community\Listeners\WriteUserActivity;
use App\Platform\Events\AchievementSetBeaten;
use App\Platform\Events\AchievementSetCompleted;
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
            WriteUserActivity::class,
        ],

        /*
         * Community Events
         */

        MessageCreated::class => [
            NotifyMessageThreadParticipants::class,
        ],

        /*
         * Platform Events - Account Listeners
         */
        AchievementSetCompleted::class => [
            WriteUserActivity::class,
        ],
        AchievementSetBeaten::class => [
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
        PlayerSessionResumed::class => [
            WriteUserActivity::class,
        ],
        PlayerSessionStarted::class => [
            WriteUserActivity::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
