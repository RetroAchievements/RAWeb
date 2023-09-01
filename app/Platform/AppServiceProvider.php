<?php

declare(strict_types=1);

namespace App\Platform;

use App\Platform\Commands\DeleteOrphanedLeaderboardEntries;
use App\Platform\Commands\NoIntroImport;
use App\Platform\Commands\SyncAchievements;
use App\Platform\Commands\SyncGameHashes;
use App\Platform\Commands\SyncGames;
use App\Platform\Commands\SyncGameSets;
use App\Platform\Commands\SyncLeaderboardEntries;
use App\Platform\Commands\SyncLeaderboards;
use App\Platform\Commands\SyncMemoryNotes;
use App\Platform\Commands\SyncPlayerAchievements;
use App\Platform\Commands\SyncPlayerBadges;
use App\Platform\Commands\SyncPlayerGames;
use App\Platform\Commands\SyncPlayerRichPresence;
use App\Platform\Commands\SyncPlayerSession;
use App\Platform\Commands\UnlockPlayerAchievement;
use App\Platform\Commands\UpdateAwardsStaticData;
use App\Platform\Commands\UpdateDeveloperContributionYield;
use App\Platform\Commands\UpdateGameAchievementsMetrics;
use App\Platform\Commands\UpdateGameMetrics;
use App\Platform\Commands\UpdateLeaderboardMetrics;
use App\Platform\Commands\UpdatePlayerGameMetrics;
use App\Platform\Commands\UpdatePlayerMasteries;
use App\Platform\Commands\UpdatePlayerMetrics;
use App\Platform\Commands\UpdatePlayerRanks;
use App\Platform\Components\GameCard;
use App\Platform\Models\Achievement;
use App\Platform\Models\Badge;
use App\Platform\Models\BadgeStage;
use App\Platform\Models\Emulator;
use App\Platform\Models\EmulatorRelease;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameHashSet;
use App\Platform\Models\GameHashSetHash;
use App\Platform\Models\GameSet;
use App\Platform\Models\GameSetGame;
use App\Platform\Models\IntegrationRelease;
use App\Platform\Models\Leaderboard;
use App\Platform\Models\LeaderboardEntryLegacy;
use App\Platform\Models\MemoryNote;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerBadgeStage;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
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
                UpdateGameMetrics::class,
                UpdateGameAchievementsMetrics::class,

                // Game Hashes
                NoIntroImport::class,

                // Leaderboards
                UpdateLeaderboardMetrics::class,
                DeleteOrphanedLeaderboardEntries::class,

                // Players
                UnlockPlayerAchievement::class,
                UpdatePlayerGameMetrics::class,
                UpdatePlayerMasteries::class,
                UpdatePlayerMetrics::class,
                UpdatePlayerRanks::class,

                // Awards & Badges
                UpdateAwardsStaticData::class,

                // Developer
                UpdateDeveloperContributionYield::class,

                // Sync
                SyncAchievements::class,
                SyncGameSets::class,
                SyncGames::class,
                SyncLeaderboards::class,
                SyncLeaderboardEntries::class,
                SyncMemoryNotes::class,
                SyncPlayerAchievements::class,
                SyncPlayerGames::class,
                SyncPlayerBadges::class,
                SyncPlayerRichPresence::class,
                SyncPlayerSession::class,
                SyncGameHashes::class,
            ]);
        }

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(DeleteOrphanedLeaderboardEntries::class)->daily();
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
            // TODO 'leaderboard-entry' => LeaderboardEntry::class,
            'leaderboard-entry' => LeaderboardEntryLegacy::class,
            'memory-note' => MemoryNote::class,
            'player.badge' => PlayerBadge::class,
            'player.badge-stage' => PlayerBadgeStage::class,
            // TODO 'player.achievement' => PlayerAchievement::class,
            'player.achievement' => PlayerAchievementLegacy::class,
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
