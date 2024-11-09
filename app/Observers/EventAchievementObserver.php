<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\PlayerAchievement;
use App\Platform\Jobs\UnlockPlayerAchievementJob;

class EventAchievementObserver
{
    public function saved(EventAchievement $eventAchievement): void
    {
        // if the source achievement or active period changed, copy the
        // relevant data to the event achievement
        $needsCopy = $eventAchievement->wasRecentlyCreated
            || $eventAchievement->wasChanged([
                'source_achievement_id', 'active_from', 'active_until',
            ]);

        if ($needsCopy) {
            $eventAchievement->loadMissing(['achievement', 'sourceAchievement']);

            $achievement = $eventAchievement->achievement;
            $sourceAchievement = $eventAchievement->sourceAchievement;

            if ($achievement && $sourceAchievement) {
                // make the event achievement look like the source achievement
                $achievement->title = $sourceAchievement->title;
                $achievement->description = $sourceAchievement->description;
                $achievement->BadgeName = $sourceAchievement->BadgeName;
                $achievement->save();

                // copy any unlocks during the active period from the source achievement to the event achievement
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
}
