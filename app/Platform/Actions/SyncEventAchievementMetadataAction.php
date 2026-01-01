<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\EventAchievement;

class SyncEventAchievementMetadataAction
{
    /* Syncs metadata from an achievement to any event achievements
     * referencing the achievement.
     * NOTE: This logic relies on passing a dirty achievement (modified but not yet saved).
     */
    public function execute(Achievement $achievement): void
    {
        if (
            $achievement->isDirty('image_name')
            || $achievement->isDirty('title')
            || $achievement->isDirty('description')
        ) {

            $eventAchievements = EventAchievement::with(['achievement'])
                ->where('source_achievement_id', $achievement->id)
                ->active()
                ->get();

            foreach ($eventAchievements as $eventAchievement) {
                $eventAchievement->achievement->image_name = $achievement->image_name;
                $eventAchievement->achievement->title = $achievement->title;
                $eventAchievement->achievement->description = $achievement->description;
                $eventAchievement->achievement->save();
            }
        }
    }
}
