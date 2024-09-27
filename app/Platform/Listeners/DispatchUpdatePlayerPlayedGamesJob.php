<?php

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Jobs\UpdatePlayerPlayedGamesJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerPlayedGamesJob implements ShouldQueue
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

        dispatch(new UpdatePlayerPlayedGamesJob($user->id))
            ->onQueue('player-game-metrics-batch');
    }
}
