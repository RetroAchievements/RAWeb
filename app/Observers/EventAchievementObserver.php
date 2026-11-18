<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\EventAchievement;
use App\Platform\Jobs\BackfillEventAchievementUnlocksJob;

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
            // Can't use loadMissing here as the relationship widget on the edit page
            // may have loaded the previous state of the achievement. Do a full refresh
            // to ensure we aren't using stale data.
            $eventAchievement = EventAchievement::with(['achievement', 'sourceAchievement'])
                ->find($eventAchievement->id);

            $achievement = $eventAchievement->achievement;
            $sourceAchievement = $eventAchievement->sourceAchievement;

            if ($achievement && $sourceAchievement) {
                // make the event achievement look like the source achievement
                $achievement->title = $sourceAchievement->title;
                $achievement->description = $sourceAchievement->description;
                $achievement->image_name = $sourceAchievement->image_name;
                $achievement->save();

                if ($achievement->is_promoted) {
                    dispatch(new BackfillEventAchievementUnlocksJob($eventAchievement->id, $achievement->game_id))
                        ->onQueue('event-backfill');
                }
            }
        }
    }
}
