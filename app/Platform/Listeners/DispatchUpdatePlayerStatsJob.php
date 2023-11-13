<?php

namespace App\Platform\Listeners;

use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Jobs\UpdatePlayerStatsJob;
use App\Site\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerStatsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case PlayerBadgeAwarded::class:
                $user = $event->user;
                break;
            case PlayerBadgeLost::class:
                $user = $event->user;
                break;
            case PlayerRankedStatusChanged::class:
                $user = $event->user;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        dispatch(new UpdatePlayerStatsJob($user->id))
            ->onQueue('player-stats');
    }
}
