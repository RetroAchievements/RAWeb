<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\EventAchievement;
use App\Models\PlayerAchievement;
use App\Platform\Jobs\UnlockPlayerAchievementJob;

class CopyAchievementUnlocksToEventAchievement
{
    public function execute(
        EventAchievement $eventAchievement
    ): void {
        $eventAchievement->loadMissing(['achievement', 'sourceAchievement']);

        $achievement = $eventAchievement->achievement;
        $sourceAchievement = $eventAchievement->sourceAchievement;

        if ($achievement && $sourceAchievement) {
            $achievement->title = $sourceAchievement->title;
            $achievement->description = $sourceAchievement->description;
            $achievement->BadgeName = $sourceAchievement->BadgeName;
            $achievement->save();

            $winners = PlayerAchievement::where('achievement_id', '=', $sourceAchievement->id)
                ->whereNotNull('unlocked_hardcore_at');

            if ($eventAchievement->active_from) {
                $winners->where('unlocked_hardcore_at', '>=', $eventAchievement->active_from);
            }
            if ($eventAchievement->active_until) {
                $winners->where('unlocked_hardcore_at', '<', $eventAchievement->active_until);
            }

            foreach ($winners->get() as $winner) {
                dispatch(new UnlockPlayerAchievementJob($winner->user_id, $achievement->id, true, $winner->unlocked_hardcore_at))
                    ->onQueue('player-achievements');
            }
        }
    }
}
