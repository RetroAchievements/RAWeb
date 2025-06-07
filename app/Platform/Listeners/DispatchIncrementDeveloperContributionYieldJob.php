<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\AchievementMaintainerUnlock;
use App\Models\User;
use App\Platform\Events\PlayerAchievementLocked;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Jobs\IncrementDeveloperContributionYieldJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchIncrementDeveloperContributionYieldJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;
        $achievement = null;
        $isUnlock = true;
        $isHardcore = false;

        switch ($event::class) {
            case PlayerAchievementUnlocked::class:
                $user = $event->user;
                $achievement = $event->achievement;
                $isUnlock = true;
                $isHardcore = $event->hardcore;
                break;

            case PlayerAchievementLocked::class:
                $user = $event->user;
                $achievement = $event->achievement;
                $isUnlock = false;

                // Resets don't have a hardcore flag.
                // We're just setting the value again here for consistency.
                $isHardcore = false;
                break;
        }

        if (!$user || !$achievement) {
            return;
        }

        $playerAchievement = $user->playerAchievements()
            ->where('achievement_id', $achievement->id)
            ->first();

        if (!$playerAchievement) {
            return;
        }

        // Check if there's a maintainer unlock record for this achievement.
        $maintainerUnlock = AchievementMaintainerUnlock::query()
            ->where('player_achievement_id', $playerAchievement->id)
            ->first();

        if ($maintainerUnlock) {
            // Mutation occurs on the maintainer.
            $developer = User::find($maintainerUnlock->maintainer_id);
        } else {
            // Mutation occurs on the original author.
            $achievement->loadMissing('developer');
            $developer = $achievement->developer;
        }

        // If we can't find the developer (or they're soft-deleted), bail.
        if (!$developer || $developer->trashed()) {
            return;
        }

        dispatch(new IncrementDeveloperContributionYieldJob(
            $developer->id,
            $achievement->id,
            $playerAchievement->id,
            $isUnlock,
            $isHardcore
        ))->onQueue('developer-metrics');
    }
}
