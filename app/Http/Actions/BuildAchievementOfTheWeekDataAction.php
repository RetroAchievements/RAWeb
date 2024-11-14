<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Models\Achievement;
use App\Models\StaticData;
use App\Platform\Data\AchievementData;

class BuildAchievementOfTheWeekDataAction
{
    // TODO fetch using event achievements, remove $staticData arg
    public function execute(?StaticData $staticData): ?AchievementData
    {
        if (!$staticData?->Event_AOTW_AchievementID) {
            return null;
        }

        $targetAchievementId = $staticData->Event_AOTW_AchievementID;

        $achievement = Achievement::find($targetAchievementId);
        if (!$achievement) {
            return null;
        }

        $achievementData = AchievementData::from($achievement)->include(
            'description',
            'badgeUnlockedUrl',
            'game.badgeUrl',
            'game.system.iconUrl',
            'game.system.nameShort',
        );

        return $achievementData;
    }
}
