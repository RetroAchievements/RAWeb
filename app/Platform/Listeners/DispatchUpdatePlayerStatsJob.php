<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Jobs\UpdatePlayerStatsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerStatsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case PlayerBadgeAwarded::class:
                $user = $event->playerBadge->user;
                break;

            case PlayerBadgeLost::class:
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
