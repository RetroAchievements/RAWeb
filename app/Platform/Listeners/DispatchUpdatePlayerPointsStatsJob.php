<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerMetricsUpdated;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Platform\Jobs\UpdatePlayerPointsStatsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdatePlayerPointsStatsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case PlayerBadgeAwarded::class:
                $user = $event->playerBadge->user;
                break;

            case PlayerMetricsUpdated::class:
            case PlayerBadgeLost::class:
            case PlayerRankedStatusChanged::class:
                $user = $event->user;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        dispatch(new UpdatePlayerPointsStatsJob($user->id))
            ->onQueue('player-points-stats');
    }
}
