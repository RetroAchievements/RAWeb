<?php

namespace App\Platform\Listeners;

use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGameMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;
        $game = null;

        switch ($event::class) {
            // TODO case AchievementPublished::class:
            //     $achievement = $event->achievement;
            //     if (is_array($achievement)) {
            //         $game = Game::find(Achievement::find($achievement)->pluck('GameID'));
            //     }
            //     break;
            // TODO case AchievementUnpublished::class:
            //     $achievement = $event->achievement;
            //     break;
            case AchievementPointsChanged::class:
                $achievement = $event->achievement;
                break;
            case PlayerGameMetricsUpdated::class:
                $game = $event->game;
                break;
        }

        if (!$game instanceof Game) {
            if (is_int($game)) {
                $game = Game::find($game);
            } elseif ($achievement instanceof Achievement) {
                $achievement->loadMissing('game');
                $game = $achievement->game;
            }
        }

        if ($game === null) {
            return;
        }

        dispatch(new UpdateGameMetricsJob($game->id))
            ->onQueue('game-metrics');
    }
}
