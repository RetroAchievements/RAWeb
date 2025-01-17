<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Models\EventAchievement;
use App\Platform\Data\EventAchievementData;

class BuildAchievementOfTheWeekDataAction
{
    public function execute(): ?EventAchievementData
    {
        $achievementOfTheWeek = EventAchievement::active()
            ->whereNotNull('active_from')
            ->whereNotNull('active_until')
            ->whereHas('achievement.game', function ($query) { // only from the current AotW event
                $query->where('Title', 'like', '%of the week%');
            })
            ->whereRaw(dateCompareStatement('active_until', 'active_from', '< 20')) // ignore AotM achievements - don't specifically look for 7 days because of the extended duration of the week 52 event
            ->with(['achievement.game', 'sourceAchievement.game'])
            ->first();

        if (!$achievementOfTheWeek?->source_achievement_id) {
            return null;
        }

        $data = EventAchievementData::from($achievementOfTheWeek)->include(
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

        return $data;
    }
}
