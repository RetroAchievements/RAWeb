<?php

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdatePlayerMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case PlayerGameMetricsUpdated::class:
                $user = $event->user;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        dispatch(new UpdatePlayerMetricsJob($user->id))
            ->onQueue('player-metrics');
    }
}
