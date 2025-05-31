<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementUnpublished;
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
            case AchievementPublished::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementUnpublished::class:
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
                ->onQueue('game-metrics');
        }

        if ($originalGame instanceof Game) {
            dispatch(new UpdateGamePlayerCountJob($originalGame->id))
                ->onQueue('game-metrics');
        }
    }
}
