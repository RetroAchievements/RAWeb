<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Platform\Support\AchievementSetDefinition;

class BuildAchievementSetDefinitionAction
{
    /**
     * @return array{version: int, achievements: array<int, array{id: int, points: int, is_promoted: bool, type: ?string}>}
     */
    public function execute(AchievementSet $achievementSet): array
    {
        $achievements = $achievementSet->relationLoaded('achievements')
            ? $achievementSet->achievements
            : $achievementSet->achievements()->get();

        $rows = $achievements
            ->map(fn (Achievement $achievement): array => [
                'id' => $achievement->id,
                'points' => $achievement->points,
                'is_promoted' => $achievement->is_promoted,
                'type' => $achievement->type,
            ])
            ->sortBy('id')
            ->values()
            ->all();

        return [
            'version' => AchievementSetDefinition::SCHEMA_VERSION,
            'achievements' => $rows,
        ];
    }
}
