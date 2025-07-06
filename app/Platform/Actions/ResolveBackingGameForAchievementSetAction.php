<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;

class ResolveBackingGameForAchievementSetAction
{
    /**
     * Resolves the backing game ID for a given achievement set.
     * The backing game is the game where this achievement set exists as type 'core'.
     *
     * For example:
     * - Pokemon Emerald (668) has achievement set 8659 as a non-core type.
     * - Pokemon Emerald [Subset - Professor Oak Challenge] (24186) has achievement set 8659 as type 'core'.
     * - The backing game ID for achievement set 8659 is 24186.
     */
    public function execute(int $achievementSetId): ?int
    {
        // Find the GameAchievementSet entry where this achievement set is marked as 'core'.
        $coreLink = GameAchievementSet::where('achievement_set_id', $achievementSetId)
            ->where('type', AchievementSetType::Core)
            ->first();

        return $coreLink?->game_id;
    }
}
