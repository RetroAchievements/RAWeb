<?php

declare(strict_types=1);

namespace App\Platform;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\BadgeStage;
use App\Models\Emulator;
use App\Models\EmulatorRelease;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\GameHashSet;
use App\Models\GameHashSetHash;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\IntegrationRelease;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\MemoryNote;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerBadgeStage;
use App\Models\PlayerSession;
use App\Models\System;
use App\Platform\Commands\BackfillAuthorYieldUnlocks;
use App\Platform\Commands\CrawlPlayerWeightedPoints;
use App\Platform\Commands\CreateAchievementOfTheWeek;
use App\Platform\Commands\DeleteStalePlayerPointsStatsEntries;
use App\Platform\Commands\NoIntroImport;
use App\Platform\Commands\ProcessExpiringClaims;
use App\Platform\Commands\PruneDuplicateSubsetNotes;
use App\Platform\Commands\PruneGameRecentPlayers;
use App\Platform\Commands\RebuildAllSearchIndexes;
use App\Platform\Commands\ResetPlayerAchievement;
use App\Platform\Commands\RevertManualUnlocks;
use App\Platform\Commands\SyncEvents;
use App\Platform\Commands\SyncUnrankedUsersTable;
use App\Platform\Commands\UnlockPlayerAchievement;
use App\Platform\Commands\UpdateAwardsStaticData;
use App\Platform\Commands\UpdateBeatenGamesLeaderboard;
use App\Platform\Commands\UpdateDeveloperContributionYield;
use App\Platform\Commands\UpdateGameAchievementsMetrics;
use App\Platform\Commands\UpdateGameBeatenMetrics;
use App\Platform\Commands\UpdateGameMetrics;
use App\Platform\Commands\UpdateGamePlayerCount;
use App\Platform\Commands\UpdateGamePlayerGames;
use App\Platform\Commands\UpdateLeaderboardMetrics;
use App\Platform\Commands\UpdatePlayerBeatenGamesStats;
use App\Platform\Commands\UpdatePlayerEstimatedTimes;
use App\Platform\Commands\UpdatePlayerGameMetrics;
use App\Platform\Commands\UpdatePlayerMetrics;
use App\Platform\Commands\UpdatePlayerPointsStats;
use App\Platform\Commands\UpdateSearchIndexForQueuedEntities;
use App\Platform\Commands\UpdateTotalGamesCount;
use App\Platform\Commands\VerifyAchievementSetIntegrity;
use App\Platform\Commands\WriteGameSetSortTitles;
use App\Platform\Commands\WriteGameSortTitles;
use App\Platform\Components\GameCard;
use App\Platform\Components\GameTitle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Games
                PruneDuplicateSubsetNotes::class,
                PruneGameRecentPlayers::class,
                UpdateGameAchievementsMetrics::class,
                UpdateGameBeatenMetrics::class,
                UpdateGameMetrics::class,
                UpdateGamePlayerCount::class,
                UpdateGamePlayerGames::class,
                VerifyAchievementSetIntegrity::class,
                WriteGameSetSortTitles::class,
                WriteGameSortTitles::class,

                // Game Hashes
                NoIntroImport::class,

                // Leaderboards
                UpdateLeaderboardMetrics::class,

                // Players
                CrawlPlayerWeightedPoints::class,
                ResetPlayerAchievement::class,
                RevertManualUnlocks::class,
                UnlockPlayerAchievement::class,
                UpdatePlayerEstimatedTimes::class,
                UpdatePlayerGameMetrics::class,
                UpdatePlayerMetrics::class,

                // Player Stats
                DeleteStalePlayerPointsStatsEntries::class,
                UpdateBeatenGamesLeaderboard::class,
                UpdatePlayerBeatenGamesStats::class,
                UpdatePlayerPointsStats::class,

                // Static Data
                UpdateAwardsStaticData::class,
                UpdateTotalGamesCount::class,

                // Search
                RebuildAllSearchIndexes::class,
                UpdateSearchIndexForQueuedEntities::class,

                // Developer
                BackfillAuthorYieldUnlocks::class,
                ProcessExpiringClaims::class,
                UpdateDeveloperContributionYield::class,

                // Events
                CreateAchievementOfTheWeek::class,

                // Sync
                SyncEvents::class,
                SyncUnrankedUsersTable::class,
            ]);
        }

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(UpdateSearchIndexForQueuedEntities::class)->twiceDaily(1, 13); // 1AM and 1PM UTC
            $schedule->command(PruneGameRecentPlayers::class)->daily();
            $schedule->command(DeleteStalePlayerPointsStatsEntries::class)->weekly();

            if (app()->environment() === 'production') {
                $schedule->command(UpdateAwardsStaticData::class)->everyMinute();
                $schedule->command(CrawlPlayerWeightedPoints::class)->everyFiveMinutes();
                $schedule->command(UpdateBeatenGamesLeaderboard::class)->everyFiveMinutes();
                $schedule->command(UpdatePlayerPointsStats::class, ['--existing-only'])->hourly();
                $schedule->command(ProcessExpiringClaims::class)->hourly();
                $schedule->command(UpdateDeveloperContributionYield::class)->weeklyOn(2, '10:00'); // Tuesdays at 10AM UTC
            }
        });

        Relation::morphMap([
            'achievement' => Achievement::class,
            'badge' => Badge::class,
            'badge-stage' => BadgeStage::class,
            'emulator' => Emulator::class,
            'emulator.release' => EmulatorRelease::class,
            'game' => Game::class,
            'game.rich-presence' => Game::class,
            'game-hash' => GameHash::class,
            'game-hash-set' => GameHashSet::class,
            'game-hash-set.game-hash' => GameHashSetHash::class,
            'game-set' => GameSet::class,
            'game-set.game' => GameSetGame::class,
            'integration.release' => IntegrationRelease::class,
            'leaderboard' => Leaderboard::class,
            'leaderboard-entry' => LeaderboardEntry::class,
            'memory-note' => MemoryNote::class,
            'player.badge' => PlayerBadge::class,
            'player.badge-stage' => PlayerBadgeStage::class,
            'player.achievement' => PlayerAchievement::class,
            'player-session' => PlayerSession::class,
            'system' => System::class,
        ]);

        // TODO remove in favor of Inertia+React components
        Blade::component('game-card', GameCard::class);
        Blade::component('game-title', GameTitle::class);
    }
}
