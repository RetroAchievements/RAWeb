<?php

declare(strict_types=1);

namespace App\Platform;

use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Events\GameMetricsUpdated;
use App\Platform\Events\PlayerAchievementLocked;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Events\PlayerGameBeaten;
use App\Platform\Events\PlayerGameCompleted;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Events\PlayerGameRemoved;
use App\Platform\Events\PlayerMetricsUpdated;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Listeners\DispatchUpdateGameMetricsJob;
use App\Platform\Listeners\DispatchUpdatePlayerGameMetricsJob;
use App\Platform\Listeners\DispatchUpdatePlayerMetricsJob;
use App\Platform\Listeners\ResetPlayerProgress;
use App\Platform\Listeners\ResumePlayerSession;
use App\Site\Events\UserDeleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AchievementCreated::class => [
        ],
        AchievementPublished::class => [
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
            // TODO DispatchUpdateDeveloperContributionYieldJob::class,
            // TODO Notify player/developer when moved to AchievementSetPublished event
        ],
        AchievementUnpublished::class => [
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
            // TODO DispatchUpdateDeveloperContributionYieldJob::class,
            // TODO Notify player/developer when moved to AchievementSetUnpublished event
        ],
        AchievementPointsChanged::class => [
            DispatchUpdateGameMetricsJob::class,
            // TODO DispatchUpdateDeveloperContributionYieldJob::class,
        ],
        AchievementTypeChanged::class => [
            DispatchUpdateGameMetricsJob::class,
        ],
        GameMetricsUpdated::class => [
        ],
        PlayerAchievementLocked::class => [
        ],
        PlayerAchievementUnlocked::class => [
            // dispatches PlayerGameAttached
            // NOTE ResumePlayerSessionAction is executed synchronously during PlayerAchievementUnlockAction
            DispatchUpdatePlayerGameMetricsJob::class, // dispatches PlayerGameMetricsUpdated
            // TODO DispatchUpdateDeveloperContributionYieldJob::class,
        ],
        PlayerBadgeAwarded::class => [
            // TODO Notify player
        ],
        PlayerBadgeLost::class => [
            // TODO Notify player
        ],
        PlayerGameAttached::class => [
            DispatchUpdatePlayerGameMetricsJob::class, // dispatches PlayerGameMetricsUpdated
        ],
        PlayerGameBeaten::class => [
            // TODO Refactor to AchievementSetBeaten
            // TODO Notify player
        ],
        PlayerGameCompleted::class => [
            // TODO Refactor to AchievementSetCompleted
            // TODO Notify player
        ],
        PlayerGameRemoved::class => [
        ],
        PlayerGameMetricsUpdated::class => [
            DispatchUpdatePlayerMetricsJob::class, // dispatches PlayerMetricsUpdated
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
        ],
        PlayerMetricsUpdated::class => [
        ],
        PlayerSessionHeartbeat::class => [
            ResumePlayerSession::class, // dispatches PlayerGameAttached for new entries
        ],
        PlayerRankedStatusChanged::class => [
            // TODO Update all affected games
            // TODO Notify player
        ],
        UserDeleted::class => [
            ResetPlayerProgress::class, // dispatches PlayerGameMetricsUpdated
        ],
    ];

    public function boot(): void
    {
    }
}
