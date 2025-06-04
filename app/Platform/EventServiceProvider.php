<?php

declare(strict_types=1);

namespace App\Platform;

use App\Events\UserDeleted;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Events\GameMetricsUpdated;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Events\PlayerAchievementLocked;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerBeatenGamesStatsUpdated;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Events\PlayerGameBeaten;
use App\Platform\Events\PlayerGameCompleted;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Events\PlayerGameRemoved;
use App\Platform\Events\PlayerMetricsUpdated;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Listeners\DispatchUpdateAchievementMetricsJob;
use App\Platform\Listeners\DispatchUpdateDeveloperContributionYieldJob;
use App\Platform\Listeners\DispatchUpdateGameBeatenMetricsJob;
use App\Platform\Listeners\DispatchUpdateGameMetricsForGamesPlayedByUserJob;
use App\Platform\Listeners\DispatchUpdateGameMetricsJob;
use App\Platform\Listeners\DispatchUpdateGamePlayerCountJob;
use App\Platform\Listeners\DispatchUpdatePlayerBeatenGamesStatsJob;
use App\Platform\Listeners\DispatchUpdatePlayerGameMetricsJob;
use App\Platform\Listeners\DispatchUpdatePlayerMetricsJob;
use App\Platform\Listeners\DispatchUpdatePlayerPointsStatsJob;
use App\Platform\Listeners\RecalculateLeaderboardTopEntriesForUser;
use App\Platform\Listeners\ResetPlayerProgress;
use App\Platform\Listeners\ResumePlayerSession;
use App\Platform\Listeners\UpdateTotalGamesCount;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AchievementCreated::class => [
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
        ],
        AchievementMoved::class => [
            DispatchUpdateGamePlayerCountJob::class,
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
        ],
        AchievementPublished::class => [
            DispatchUpdateGamePlayerCountJob::class,
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
            DispatchUpdateDeveloperContributionYieldJob::class, // dispatches UpdateDeveloperContributionYield
            UpdateTotalGamesCount::class,
            // TODO Notify player/developer when moved to AchievementSetPublished event
        ],
        AchievementUnpublished::class => [
            DispatchUpdateGamePlayerCountJob::class,
            DispatchUpdateGameMetricsJob::class, // dispatches GameMetricsUpdated
            DispatchUpdateDeveloperContributionYieldJob::class, // dispatches UpdateDeveloperContributionYield
            UpdateTotalGamesCount::class,
            // TODO Notify player/developer when moved to AchievementSetUnpublished event
        ],
        AchievementPointsChanged::class => [
            DispatchUpdateGameMetricsJob::class,
            DispatchUpdateDeveloperContributionYieldJob::class, // dispatches UpdateDeveloperContributionYield
            DispatchUpdateAchievementMetricsJob::class,
        ],
        AchievementTypeChanged::class => [
            DispatchUpdateGameMetricsJob::class,
        ],
        GameMetricsUpdated::class => [
        ],
        GamePlayerGameMetricsUpdated::class => [
            DispatchUpdateGamePlayerCountJob::class,
        ],
        PlayerAchievementLocked::class => [
            DispatchUpdateAchievementMetricsJob::class,
            DispatchUpdatePlayerGameMetricsJob::class, // dispatches PlayerGameMetricsUpdated
            DispatchUpdateDeveloperContributionYieldJob::class, // dispatches UpdateDeveloperContributionYield
        ],
        PlayerAchievementUnlocked::class => [
            // dispatches PlayerGameAttached
            // NOTE ResumePlayerSessionAction is executed synchronously during PlayerAchievementUnlockAction
            DispatchUpdateAchievementMetricsJob::class,
            DispatchUpdatePlayerGameMetricsJob::class, // dispatches PlayerGameMetricsUpdated
            DispatchUpdateDeveloperContributionYieldJob::class, // dispatches UpdateDeveloperContributionYield
        ],
        PlayerBadgeAwarded::class => [
            // TODO Notify player
            DispatchUpdateGameBeatenMetricsJob::class,
            DispatchUpdatePlayerBeatenGamesStatsJob::class, // dispatches PlayerBeatenGamesStatsUpdated
        ],
        PlayerBadgeLost::class => [
            // TODO Notify player
            DispatchUpdateGameBeatenMetricsJob::class,
            DispatchUpdatePlayerBeatenGamesStatsJob::class, // dispatches PlayerBeatenGamesStatsUpdated
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
        ],
        PlayerMetricsUpdated::class => [
            DispatchUpdatePlayerPointsStatsJob::class,
        ],
        PlayerSessionHeartbeat::class => [
            ResumePlayerSession::class, // dispatches PlayerGameAttached for new entries
        ],
        PlayerRankedStatusChanged::class => [
            DispatchUpdateGameMetricsForGamesPlayedByUserJob::class,
            // TODO Notify player
            DispatchUpdatePlayerBeatenGamesStatsJob::class, // dispatches PlayerBeatenGamesStatsUpdated
            DispatchUpdatePlayerPointsStatsJob::class,
            RecalculateLeaderboardTopEntriesForUser::class,
        ],
        PlayerBeatenGamesStatsUpdated::class => [
        ],
        UserDeleted::class => [
            RecalculateLeaderboardTopEntriesForUser::class,
            ResetPlayerProgress::class, // dispatches PlayerGameMetricsUpdated
        ],
    ];

    public function boot(): void
    {
    }
}
