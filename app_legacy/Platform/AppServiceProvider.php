<?php

declare(strict_types=1);

namespace LegacyApp\Platform;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use LegacyApp\Platform\Commands\DeleteOrphanedLeaderboardEntries;
use LegacyApp\Platform\Commands\UpdateDeveloperContributionYield;
use LegacyApp\Platform\Commands\UpdateGameWeightedPoints;
use LegacyApp\Platform\Commands\UpdatePlayerMasteries;
use LegacyApp\Platform\Commands\UpdatePlayerPoints;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\GameHash;
use LegacyApp\Platform\Models\Leaderboard;
use LegacyApp\Platform\Models\LeaderboardEntry;
use LegacyApp\Platform\Models\MemoryNote;
use LegacyApp\Platform\Models\PlayerAchievement;
use LegacyApp\Platform\Models\PlayerBadge;
use LegacyApp\Platform\Models\System;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeleteOrphanedLeaderboardEntries::class,
                UpdateDeveloperContributionYield::class,
                UpdateGameWeightedPoints::class,
                UpdatePlayerPoints::class,
                UpdatePlayerMasteries::class,
            ]);
        }

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(UpdateDeveloperContributionYield::class)->everyMinute();
            $schedule->command(UpdateGameWeightedPoints::class)->everyMinute();
            $schedule->command(UpdatePlayerPoints::class)->everyMinute();

            $schedule->command(DeleteOrphanedLeaderboardEntries::class)->daily();
        });

        Relation::morphMap([
            'achievement' => Achievement::class,
            'game' => Game::class,
            'leaderboard' => Leaderboard::class,
            'leaderboard-entry' => LeaderboardEntry::class,
            'memory-note' => MemoryNote::class,
            'game-hash' => GameHash::class,
            'system' => System::class,
            'player-achievement' => PlayerAchievement::class,
            'player-badge' => PlayerBadge::class,
        ]);
    }
}
