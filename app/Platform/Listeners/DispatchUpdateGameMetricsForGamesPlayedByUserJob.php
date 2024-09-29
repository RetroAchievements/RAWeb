<?php

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Jobs\UpdateGameMetricsForGamesPlayedByUserJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGameMetricsForGamesPlayedByUserJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case PlayerRankedStatusChanged::class:
                $user = $event->user;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        dispatch(new UpdateGameMetricsForGamesPlayedByUserJob($user->id))
            ->onQueue('player-game-metrics-batch');
    }
}
