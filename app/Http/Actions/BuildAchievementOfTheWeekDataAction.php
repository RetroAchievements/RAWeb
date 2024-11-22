<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\StaticData;
use App\Platform\Data\EventAchievementData;

class BuildAchievementOfTheWeekDataAction
{
    // TODO remove $staticData arg once event is actually run using EventAchievements
    public function execute(?StaticData $staticData): ?EventAchievementData
    {
        $achievementOfTheWeek = EventAchievement::active()
            ->whereHas('achievement.game', function ($query) {
                $query->where('Title', 'like', '%of the week%');
            })
            ->with(['achievement.game', 'sourceAchievement.game'])
            ->first();

        if (!$achievementOfTheWeek || !$achievementOfTheWeek->source_achievement_id) {
            if (!$staticData?->Event_AOTW_AchievementID) {
                return null;
            }

            $targetAchievementId = $staticData->Event_AOTW_AchievementID;

            $achievement = Achievement::find($targetAchievementId);
            if (!$achievement) {
                return null;
            }

            // make a new EventAchievment object (and modify the related records) to
            // mimic the behavior of a valid EventAchievement. DO NOT SAVE THESE!
            $achievement->game->ForumTopicID = $staticData->Event_AOTW_ForumID;

            $achievementOfTheWeek = new EventAchievement();
            $achievementOfTheWeek->setRelation('achievement', $achievement);
            $achievementOfTheWeek->setRelation('sourceAchievement', $achievement);
        }

        $data = EventAchievementData::from($achievementOfTheWeek)->include(
            'achievement.id',
            'achievement.title',
            'achievement.description',
            'achievement.badgeUnlockedUrl',
            'sourceAchievement.game.badgeUrl',
            'sourceAchievement.game.system.iconUrl',
            'sourceAchievement.game.system.nameShort',
            'forumTopicId',
            'activeUntil',
        );

        return $data;
    }
}
