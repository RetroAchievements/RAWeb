<?php

namespace App\Platform\Listeners;

use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateDeveloperContributionYieldJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            // TODO case AchievementPublished::class:
            //     $achievement = $event->achievement;
            //     $achievement->loadMissing('developer');
            //     $user = $achievement->developer;
            //     break;
            // TODO case AchievementUnpublished::class:
            //     $achievement = $event->achievement;
            //     $achievement->loadMissing('developer');
            //     $user = $achievement->developer;
            //     break;
            case AchievementPointsChanged::class:
                $achievement = $event->achievement;
                $achievement->loadMissing('developer');
                $user = $achievement->developer;
                break;
            case PlayerAchievementUnlocked::class:
                $achievement = $event->achievement;
                $achievement->loadMissing('developer');
                $user = $achievement->developer;
                break;
        }

        if ($user === null) {
            return;
        }

        dispatch(new UpdateDeveloperContributionYieldJob($user->id))
            ->onQueue('developer-metrics');
    }
}
