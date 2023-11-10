<?php

namespace App\Platform\Listeners;

use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGameMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;
        $game = null;

        switch ($event::class) {
            case AchievementPublished::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case AchievementUnpublished::class:
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
            case GamePlayerGameMetricsUpdated::class:
                $game = $event->game;
                break;
            case PlayerGameMetricsUpdated::class:
                $game = $event->game;
                break;
        }

        if (!$game instanceof Game) {
            return;
        }

        dispatch(new UpdateGameMetricsJob($game->id))
            ->onQueue('game-metrics');
    }
}
