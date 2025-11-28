<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Collection;

class FilterGameIdsToSubsetGameIdsAction
{
    /**
     * Filter game IDs to only those that are subset games (core sets that also exist as non-core types elsewhere).
     *
     * @param array<int>|Collection<int, int> $gameIds
     * @return array<int>
     */
    public function execute(array|Collection $gameIds): array
    {
        $gameIds = $gameIds instanceof Collection ? $gameIds : collect($gameIds);
        $gameIds = $gameIds->filter()->unique();

        if ($gameIds->isEmpty()) {
            return [];
        }

        return GameAchievementSet::whereIn('game_id', $gameIds)
            ->where('type', AchievementSetType::Core)
            ->whereExists(function ($query) {
                $query->select('*')
                    ->from('game_achievement_sets as gas2')
                    ->whereColumn('gas2.achievement_set_id', 'game_achievement_sets.achievement_set_id')
                    ->whereColumn('gas2.game_id', '!=', 'game_achievement_sets.game_id')
                    ->where('gas2.type', '!=', AchievementSetType::Core);
            })
            ->pluck('game_id')
            ->toArray();
    }
}
