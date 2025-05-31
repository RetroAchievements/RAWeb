<?php

namespace App\Platform\Listeners;

use App\Models\Achievement;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\PlayerAchievementLocked;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Jobs\UpdateAchievementMetricsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateAchievementMetricsJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;

        switch ($event::class) {
            case PlayerAchievementLocked::class:
                $achievement = $event->achievement;
                break;
            case PlayerAchievementUnlocked::class:
                $achievement = $event->achievement;
                break;
            case AchievementPointsChanged::class:
                $achievement = $event->achievement;
                break;
        }

        if ($achievement instanceof Achievement) {
            dispatch(new UpdateAchievementMetricsJob($achievement->id))
                ->onQueue('achievement-metrics');
        }
    }
}
