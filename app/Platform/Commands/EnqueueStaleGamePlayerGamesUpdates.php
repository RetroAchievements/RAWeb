<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerAchievement;
use App\Platform\Jobs\UpdateGamePlayerGamesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnqueueStaleGamePlayerGamesUpdates extends Command
{
    protected $signature = 'ra:platform:game:enqueue-stale-player-games-updates
                            {minutes=15 : The number of minutes worth of achievement unlocks to fetch target game IDs for}';
    protected $description = 'Find unique game IDs with recent hardcore unlocks and update player game metrics for them';

    public function handle(): void
    {
        $minutes = (int) $this->argument('minutes');

        $now = Carbon::now();
        $timeRange = $now->copy()->subMinutes($minutes);

        $this->info('Determining game IDs to update metrics for.');

        $gameIds = PlayerAchievement::whereBetween("unlocked_hardcore_at", [
            $timeRange,
            $now,
        ])
            ->join(
              "Achievements",
              "player_achievements.achievement_id",
              "=",
              "Achievements.ID"
            )
            ->select("Achievements.GameID")
            ->distinct()
            ->pluck("Achievements.GameID");

        $this->info('Dispatching jobs for ' . $gameIds->count() . ' game IDs.');

        foreach ($gameIds as $gameId) {
            dispatch(new UpdateGamePlayerGamesJob($gameId))
                ->onQueue('game-player-games');
        }
    }
}
