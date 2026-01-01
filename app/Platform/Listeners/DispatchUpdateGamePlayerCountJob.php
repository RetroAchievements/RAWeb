<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPromoted;
use App\Platform\Events\AchievementUnpromoted;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdateGamePlayerCountJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGamePlayerCountJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;
        $game = null;
        $originalGame = null;

        switch ($event::class) {
            case AchievementPromoted::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementUnpromoted::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementMoved::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                $originalGame = $event->originalGame;
                break;
            case GamePlayerGameMetricsUpdated::class:
                $game = $event->game;
                break;
        }

        if ($game instanceof Game) {
            dispatch(new UpdateGamePlayerCountJob($game->id))
                ->onQueue('game-player-count');
        }

        if ($originalGame instanceof Game) {
            dispatch(new UpdateGamePlayerCountJob($originalGame->id))
                ->onQueue('game-player-count');
        }
    }
}
