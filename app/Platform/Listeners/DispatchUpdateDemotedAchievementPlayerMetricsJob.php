<?php

namespace App\Platform\Listeners;

use App\Models\Achievement;
use App\Platform\Events\AchievementDeleted;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementRestored;
use App\Platform\Jobs\UpdateDemotedAchievementPlayerMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateDemotedAchievementPlayerMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;

        switch ($event::class) {
            case AchievementDeleted::class:
            case AchievementPointsChanged::class:
            case AchievementRestored::class:
                $achievement = $event->achievement;
                break;
        }

        if (!$achievement instanceof Achievement || !$achievement->should_keep_demoted_points) {
            return;
        }

        dispatch(new UpdateDemotedAchievementPlayerMetricsJob($achievement->id))
            ->onQueue('player-metrics');
    }
}
