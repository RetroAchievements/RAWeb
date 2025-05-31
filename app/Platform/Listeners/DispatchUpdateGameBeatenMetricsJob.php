<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Jobs\UpdateGameBeatenMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGameBeatenMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $game = null;

        switch ($event::class) {
            case PlayerBadgeAwarded::class:
                // this probably should watch for PlayerGameBeaten and PlayerGameCompleted,
                // but it's easier to just watch for the badge being awarded, which
                // complements the behavior of watching for the badge disappearing.
                switch ($event->playerBadge->AwardType) {
                    case AwardType::GameBeaten:
                    case AwardType::Mastery:
                        $game = Game::find($event->playerBadge->AwardData);
                        break;
                }
                break;

            case PlayerBadgeLost::class:
                switch ($event->awardType) {
                    case AwardType::GameBeaten:
                    case AwardType::Mastery:
                        $game = Game::find($event->awardData);
                        break;
                }
                break;
        }

        if ($game instanceof Game) {
            dispatch(new UpdateGameBeatenMetricsJob($game->id))
                ->onQueue('game-metrics');
        }
    }
}
