<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Http\Data\AchievementOfTheWeekPropsData;
use App\Models\EventAchievement;
use App\Models\User;
use App\Platform\Data\EventAchievementData;

class BuildAchievementOfTheWeekDataAction
{
    public function execute(?User $user = null): ?AchievementOfTheWeekPropsData
    {
        $achievementOfTheWeek = EventAchievement::currentAchievementOfTheWeek()
            ->with(['event', 'achievement.game', 'sourceAchievement.game'])
            ->first();

        if (!$achievementOfTheWeek?->sourceAchievement) {
            return null;
        }

        $currentEventAchievementData = EventAchievementData::from($achievementOfTheWeek)->include(
            'achievement.id',
            'achievement.title',
            'achievement.description',
            'achievement.badgeUnlockedUrl',
            'sourceAchievement.game.badgeUrl',
            'sourceAchievement.game.system.iconUrl',
            'sourceAchievement.game.system.nameShort',
            'event',
            'event.legacyGame',
            'activeUntil',
        );

        return new AchievementOfTheWeekPropsData(
            currentEventAchievement: $currentEventAchievementData,
            achievementOfTheWeekProgress: $user
                ? (new CalculateAchievementOfTheWeekUserProgressAction())->execute($user, $achievementOfTheWeek->event)
                : null,
        );
    }
}
