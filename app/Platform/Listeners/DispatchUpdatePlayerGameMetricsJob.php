<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Models\User;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerGameMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;
        $game = null;

        /**
         * Keep full recounts unless another job is already refreshing the changed unlock total.
         * Live unlocks dispatch UpdateAchievementMetricsJob separately, so their player count
         * cascade only needs to recompute percentages from stored unlock counts.
         */
        $shouldRecalculateAchievementUnlockCounts = true;

        switch ($event::class) {
            case PlayerAchievementUnlocked::class:
                $user = $event->user;
                $achievement = $event->achievement;
                $game = $achievement->game;
                $shouldRecalculateAchievementUnlockCounts = false;
                break;
            case PlayerGameAttached::class:
                $user = $event->user;
                $game = $event->game;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        if (!$game instanceof Game) {
            return;
        }

        dispatch(new UpdatePlayerGameMetricsJob(
            $user->id,
            $game->id,
            shouldRecalculateAchievementUnlockCounts: $shouldRecalculateAchievementUnlockCounts
        ))
            ->onQueue('player-game-metrics');
    }
}
