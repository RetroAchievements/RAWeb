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
         * PlayerAchievementUnlocked kicks off several separate metrics updates.
         *
         * UpdateAchievementMetricsJob recounts the unlocked achievement's
         * denormalized totals from player_achievements. This player game metrics
         * path may also update the game's player count, which means aggregate
         * achievement percentages and RetroPoints (weighted points) need to be
         * refreshed for the game.
         *
         * Since the achievement job owns the expensive unlock recount, this path
         * can reuse the stored unlock totals and avoid running that same recount again.
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
