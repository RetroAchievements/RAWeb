<?php

declare(strict_types=1);

namespace LegacyApp\Platform;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use LegacyApp\Platform\Commands\Developer\RecalculateContributionYield;
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
                RecalculateContributionYield::class,
            ]);
        }

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
