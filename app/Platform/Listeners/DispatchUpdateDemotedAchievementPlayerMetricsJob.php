<?php

namespace App\Platform\Listeners;

use App\Models\Achievement;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Jobs\UpdateDemotedAchievementPlayerMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateDemotedAchievementPlayerMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;

        switch ($event::class) {
            case AchievementPointsChanged::class:
                $achievement = $event->achievement;
                break;
        }

        if (!$achievement instanceof Achievement || !$achievement->retainsPointsForPriorUnlocks()) {
            return;
        }

        dispatch(new UpdateDemotedAchievementPlayerMetricsJob($achievement->id))
            ->onQueue('player-metrics');
    }
}
