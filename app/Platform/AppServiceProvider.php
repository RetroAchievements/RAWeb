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
use App\Platform\Commands\BackfillPlaytimeTotal;
use App\Platform\Commands\CreateAchievementOfTheWeek;
use App\Platform\Commands\DeleteStalePlayerPointsStatsEntries;
use App\Platform\Commands\MigrateMissableAchievementsToType;
use App\Platform\Commands\NoIntroImport;
use App\Platform\Commands\ResetPlayerAchievement;
use App\Platform\Commands\SendClaimExpirationWarningEmails;
use App\Platform\Commands\SyncAchievementAuthors;
use App\Platform\Commands\SyncAchievements;
use App\Platform\Commands\SyncAchievementSetImageAssetPaths;
use App\Platform\Commands\SyncEvents;
use App\Platform\Commands\SyncGameAchievementSets;
use App\Platform\Commands\SyncGameHashes;
use App\Platform\Commands\SyncGameReleases;
use App\Platform\Commands\SyncGames;
use App\Platform\Commands\SyncLeaderboards;
use App\Platform\Commands\SyncLeaderboardTopEntries;
use App\Platform\Commands\SyncLegacyGameTags;
use App\Platform\Commands\SyncMemoryNotes;
use App\Platform\Commands\SyncPlayerBadges;
use App\Platform\Commands\SyncPlayerRichPresence;
use App\Platform\Commands\SyncPlayerSession;
use App\Platform\Commands\SyncTriggers;
use App\Platform\Commands\TrimGameMetadata;
use App\Platform\Commands\UnlockPlayerAchievement;
use App\Platform\Commands\UpdateAwardsStaticData;
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
                TrimGameMetadata::class,
                UpdateGameAchievementsMetrics::class,
                UpdateGameBeatenMetrics::class,
                UpdateGameMetrics::class,
                UpdateGamePlayerCount::class,
                UpdateGamePlayerGames::class,
                VerifyAchievementSetIntegrity::class,
                WriteGameSortTitles::class,

                // Game Hashes
                NoIntroImport::class,

                // Achievements
                MigrateMissableAchievementsToType::class,

                // Leaderboards
                UpdateLeaderboardMetrics::class,

                // Players
                BackfillPlaytimeTotal::class,
                ResetPlayerAchievement::class,
                UnlockPlayerAchievement::class,
                UpdatePlayerEstimatedTimes::class,
                UpdatePlayerGameMetrics::class,
                UpdatePlayerMetrics::class,

                // Player Stats
                DeleteStalePlayerPointsStatsEntries::class,
                UpdatePlayerBeatenGamesStats::class,
                UpdatePlayerPointsStats::class,

                // Static Data
                UpdateAwardsStaticData::class,
                UpdateTotalGamesCount::class,

                // Search
                UpdateSearchIndexForQueuedEntities::class,

                // Developer
                SendClaimExpirationWarningEmails::class,
                UpdateDeveloperContributionYield::class,

                // Events
                CreateAchievementOfTheWeek::class,

                // Sync
                SyncAchievementAuthors::class,
                SyncAchievements::class,
                SyncAchievementSetImageAssetPaths::class,
                SyncEvents::class,
                SyncGameAchievementSets::class,
                SyncGameHashes::class,
                SyncGames::class,
                SyncGameReleases::class,
                SyncLeaderboards::class,
                SyncLeaderboardTopEntries::class,
                SyncLegacyGameTags::class,
                SyncMemoryNotes::class,
                SyncPlayerBadges::class,
                SyncPlayerRichPresence::class,
                SyncPlayerSession::class,
                SyncTriggers::class,
            ]);
        }

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(UpdateAwardsStaticData::class)->everyMinute();
            $schedule->command(BackfillPlaytimeTotal::class)->everyFifteenMinutes();
            $schedule->command(UpdatePlayerPointsStats::class, ['--existing-only'])->hourly();
            $schedule->command(SendClaimExpirationWarningEmails::class)->hourly();
            $schedule->command(UpdateSearchIndexForQueuedEntities::class)->twiceDaily(1, 13); // 1AM and 1PM
            $schedule->command(DeleteStalePlayerPointsStatsEntries::class)->weekly();
        });

        $this->loadMigrationsFrom([database_path('migrations/platform')]);

        Relation::morphMap([
            'achievement' => Achievement::class,
            'badge' => Badge::class,
            'badge-stage' => BadgeStage::class,
            'emulator' => Emulator::class,
            'emulator.release' => EmulatorRelease::class,
            'game' => Game::class,
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

        Blade::component('game-card', GameCard::class);
        Blade::component('game-title', GameTitle::class);

        // Livewire::component('achievement-grid', AchievementGrid::class);
        // Livewire::component('achievement-player-grid', AchievementPlayerGrid::class);
        // Livewire::component('badge-grid', BadgeGrid::class);
        // Livewire::component('game-grid', GameGrid::class);
        // Livewire::component('game-player-grid', GamePlayerGrid::class);
        // Livewire::component('leaderboard-grid', LeaderboardGrid::class);
        // Livewire::component('game-hash-grid', GameHashGrid::class);
        // Livewire::component('system-grid', SystemGrid::class);
        //
        // Livewire::component('players-active', PlayersActive::class);
        //
        // Livewire::component('emulator-grid', EmulatorGrid::class);
        // Livewire::component('emulator-release-grid', EmulatorReleaseGrid::class);
        // Livewire::component('integration-release-grid', IntegrationReleaseGrid::class);
    }
}
