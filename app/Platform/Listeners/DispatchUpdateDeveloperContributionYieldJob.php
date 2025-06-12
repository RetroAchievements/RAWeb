<?php

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;

class DispatchUpdateDeveloperContributionYieldJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case AchievementPublished::class:
            case AchievementUnpublished::class:
            case AchievementPointsChanged::class:
                $achievement = $event->achievement;

                $user = $achievement->getMaintainerAt(Carbon::now());
                if (!$user) {
                    $achievement->loadMissing('developer');
                    $user = $achievement->developer;
                }

                break;
        }

        if (!$user instanceof User || $user->trashed()) {
            return;
        }

        dispatch(new UpdateDeveloperContributionYieldJob($user->id))
            ->onQueue('developer-metrics');
    }
}
