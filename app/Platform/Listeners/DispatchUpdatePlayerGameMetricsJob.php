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
            if (is_int($user)) {
                $user = User::find($user);
            } elseif (is_string($user)) {
                $user = User::firstWhere('User', $user);
            }
        }

        if (is_int($game)) {
            $game = Game::find($game);
        }

        if ($user === null || $game === null) {
            return;
        }

        dispatch(new UpdatePlayerGameMetricsJob($user->id, $game->id, $hardcore))
            ->onQueue('player-game-metrics');
    }
}
