<?php

namespace App\Platform\Listeners;

use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdatePlayerMetricsJob;
use App\Site\Models\User;
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
            if (is_int($user)) {
                $user = User::find($user);
            } elseif (is_string($user)) {
                $user = User::firstWhere('User', $user);
            }
        }

        if ($user === null) {
            return;
        }

        dispatch(new UpdatePlayerMetricsJob($user->id))
            ->onQueue('player-metrics');
    }
}
