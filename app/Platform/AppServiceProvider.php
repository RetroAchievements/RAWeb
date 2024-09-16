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
use App\Platform\Commands\ConvertGameReleasedToTimestamp;
use App\Platform\Commands\DeleteStalePlayerPointsStatsEntries;
use App\Platform\Commands\EnqueueStaleGamePlayerGamesUpdates;
use App\Platform\Commands\MigrateMissableAchievementsToType;
use App\Platform\Commands\NoIntroImport;
use App\Platform\Commands\ResetPlayerAchievement;
use App\Platform\Commands\SyncAchievements;
use App\Platform\Commands\SyncGameAchievementSets;
use App\Platform\Commands\SyncGameHashes;
use App\Platform\Commands\SyncGames;
use App\Platform\Commands\SyncGameSets;
use App\Platform\Commands\SyncLeaderboardEntries;
use App\Platform\Commands\SyncLeaderboards;
use App\Platform\Commands\SyncMemoryNotes;
use App\Platform\Commands\SyncPlayerBadges;
use App\Platform\Commands\SyncPlayerRichPresence;
use App\Platform\Commands\SyncPlayerSession;
use App\Platform\Commands\TrimGameMetadata;
use App\Platform\Commands\UnlockPlayerAchievement;
use App\Platform\Commands\UpdateAwardsStaticData;
use App\Platform\Commands\UpdateDeveloperContributionYield;
use App\Platform\Commands\UpdateGameAchievementsMetrics;
use App\Platform\Commands\UpdateGameMetrics;
use App\Platform\Commands\UpdateGamePlayerGames;
use App\Platform\Commands\UpdateLeaderboardMetrics;
use App\Platform\Commands\UpdatePlayerBeatenGamesStats;
use App\Platform\Commands\UpdatePlayerGameMetrics;
use App\Platform\Commands\UpdatePlayerMetrics;
use App\Platform\Commands\UpdatePlayerPointsStats;
use App\Platform\Commands\UpdateTotalGamesCount;
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
                ConvertGameReleasedToTimestamp::class,
                EnqueueStaleGamePlayerGamesUpdates::class,
                TrimGameMetadata::class,
                UpdateGameMetrics::class,
                UpdateGameAchievementsMetrics::class,
                UpdateGamePlayerGames::class,

                // Game Hashes
                NoIntroImport::class,

                // Achievements
                MigrateMissableAchievementsToType::class,

                // Leaderboards
                UpdateLeaderboardMetrics::class,

                // Players
                ResetPlayerAchievement::class,
                UnlockPlayerAchievement::class,
                UpdatePlayerGameMetrics::class,
                UpdatePlayerMetrics::class,

                // Player Stats
                DeleteStalePlayerPointsStatsEntries::class,
                UpdatePlayerBeatenGamesStats::class,
                UpdatePlayerPointsStats::class,

                // Static Data
                UpdateAwardsStaticData::class,
                UpdateTotalGamesCount::class,

                // Developer
                UpdateDeveloperContributionYield::class,

                // Sync
                SyncAchievements::class,
                SyncGameAchievementSets::class,
                SyncGameSets::class,
                SyncGames::class,
                SyncLeaderboards::class,
                SyncLeaderboardEntries::class,
                SyncMemoryNotes::class,
                SyncPlayerBadges::class,
                SyncPlayerRichPresence::class,
                SyncPlayerSession::class,
                SyncGameHashes::class,
            ]);
        }

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(UpdateAwardsStaticData::class)->everyMinute();
            // $schedule->command(EnqueueStaleGamePlayerGamesUpdates::class)->everyFifteenMinutes();
            $schedule->command(UpdatePlayerPointsStats::class, ['--existing-only'])->hourly();
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

        Achievement::disableSearchSyncing();
        Badge::disableSearchSyncing();
        Game::disableSearchSyncing();
        GameHash::disableSearchSyncing();
        Leaderboard::disableSearchSyncing();
        System::disableSearchSyncing();

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
