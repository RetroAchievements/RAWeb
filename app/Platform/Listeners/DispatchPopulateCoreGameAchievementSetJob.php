<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Platform\Jobs\PopulateCoreGameAchievementSetJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchPopulateCoreGameAchievementSetJob implements ShouldQueue
{
    // TODO - implement for double writes
    public function handle(object $event): void
    {
        // $game = null;

        // // TODO
        // switch ($event::class) {
        //     default:
        //         break;
        // }

        // if (!$game instanceof Game) {
        //     return;
        // }

        // dispatch(new PopulateCoreGameAchievementSetJob($game->id))
        //     ->onQueue('core-game-achievement-sets');
    }
}
