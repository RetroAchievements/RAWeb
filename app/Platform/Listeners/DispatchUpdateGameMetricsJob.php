<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementDeleted;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPromoted;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpromoted;
use App\Platform\Jobs\UpdateGameMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGameMetricsJob implements ShouldQueue
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
            case AchievementPointsChanged::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementTypeChanged::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementCreated::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementDeleted::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementMoved::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                $originalGame = $event->originalGame;
                break;
        }

        if ($game instanceof Game) {
            dispatch(new UpdateGameMetricsJob($game->id))
                ->onQueue('game-metrics');
        }

        if ($originalGame instanceof Game) {
            dispatch(new UpdateGameMetricsJob($originalGame->id))
                ->onQueue('game-metrics');
        }
    }
}
