<?php

namespace App\Platform\Listeners;

use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerGameMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;
        $game = null;
        // TODO forward hardcore flag
        $hardcore = null;

        switch ($event::class) {
            case PlayerAchievementUnlocked::class:
                $user = $event->user;
                $achievement = $event->achievement;
                $game = $achievement->game;
                $hardcore = $event->hardcore;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        if (!$game instanceof Game) {
            return;
        }

        dispatch(new UpdatePlayerGameMetricsJob($user->id, $game->id))
            ->onQueue('player-game-metrics');
    }
}
