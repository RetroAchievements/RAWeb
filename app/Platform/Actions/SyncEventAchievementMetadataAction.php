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
        if ($achievement->isDirty('BadgeName')
            || $achievement->isDirty('Title')
            || $achievement->isDirty('Description')) {

            $eventAchievements = EventAchievement::with(['achievement'])
                ->where('source_achievement_id', $achievement->id)
                ->active()
                ->get();

            foreach ($eventAchievements as $eventAchievement) {
                $eventAchievement->achievement->BadgeName = $achievement->BadgeName;
                $eventAchievement->achievement->Title = $achievement->Title;
                $eventAchievement->achievement->Description = $achievement->Description;
                $eventAchievement->achievement->save();
            }
        }
    }
}
