<?php

declare(strict_types=1);

namespace App\Platform;

use App\Platform\Commands\NoIntroImport;
use App\Platform\Commands\RecalcContribYield;
use App\Platform\Commands\SyncAchievements;
use App\Platform\Commands\SyncGameHashes;
use App\Platform\Commands\SyncGameRelations;
use App\Platform\Commands\SyncGames;
use App\Platform\Commands\SyncLeaderboardEntries;
use App\Platform\Commands\SyncLeaderboards;
use App\Platform\Commands\SyncMemoryNotes;
use App\Platform\Commands\SyncPlayerAchievements;
use App\Platform\Commands\SyncPlayerBadges;
use App\Platform\Commands\SyncPlayerRichPresence;
use App\Platform\Commands\SyncSystems;
use App\Platform\Commands\UnlockPlayerAchievement;
use App\Platform\Commands\UpdateAllAchievementsMetrics;
use App\Platform\Commands\UpdateAllGamesMetrics;
use App\Platform\Commands\UpdateAllPlayerGamesMetrics;
use App\Platform\Commands\UpdateGameMetrics;
use App\Platform\Commands\UpdateLeaderboardMetrics;
use App\Platform\Commands\UpdatePlayerGameMetrics;
use App\Platform\Commands\UpdatePlayerMetrics;
use App\Platform\Commands\UpdatePlayerRanks;
use App\Platform\Models\Achievement;
use App\Platform\Models\Badge;
use App\Platform\Models\BadgeStage;
use App\Platform\Models\Emulator;
use App\Platform\Models\EmulatorRelease;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameHashSet;
use App\Platform\Models\GameHashSetHash;
use App\Platform\Models\IntegrationRelease;
use App\Platform\Models\Leaderboard;
use App\Platform\Models\LeaderboardEntry;
use App\Platform\Models\MemoryNote;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerBadgeStage;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                /*
                 * no-intro
                 */
                NoIntroImport::class,

                /*
                 * Server
                 */
                RecalcContribYield::class,

                UnlockPlayerAchievement::class,

                UpdateAllAchievementsMetrics::class,

                UpdateGameMetrics::class,
                UpdateAllGamesMetrics::class,

                UpdateLeaderboardMetrics::class,

                UpdatePlayerMetrics::class,
                UpdatePlayerRanks::class,

                UpdatePlayerGameMetrics::class,
                UpdateAllPlayerGamesMetrics::class,

                /*
                 * Sync
                 */
                SyncAchievements::class,
                SyncGameRelations::class,
                SyncGames::class,
                SyncLeaderboards::class,
                SyncLeaderboardEntries::class,
                SyncMemoryNotes::class,
                SyncPlayerAchievements::class,
                SyncPlayerBadges::class,
                SyncPlayerRichPresence::class,
                SyncGameHashes::class,
                SyncSystems::class,
            ]);
        }

        $this->loadMigrationsFrom([database_path('migrations/platform')]);

        Relation::morphMap([
            'achievement' => Achievement::class,
            'badge' => Badge::class,
            'badge-stage' => BadgeStage::class,
            'game' => Game::class,
            'leaderboard' => Leaderboard::class,
            'leaderboard-entry' => LeaderboardEntry::class,
            'memory-note' => MemoryNote::class,
            'game-hash' => GameHash::class,
            'game-hash-set' => GameHashSet::class,
            'game-hash-set.game-hash' => GameHashSetHash::class,
            'system' => System::class,

            'player.badge' => PlayerBadge::class,
            'player.badge-stage' => PlayerBadgeStage::class,
            'player-achievement' => PlayerAchievement::class,
            'player-session' => PlayerSession::class,
            'emulator' => Emulator::class,
            'emulator.release' => EmulatorRelease::class,
            'integration.release' => IntegrationRelease::class,
        ]);

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
