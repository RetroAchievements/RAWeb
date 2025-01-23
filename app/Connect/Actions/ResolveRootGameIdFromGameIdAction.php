<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;

/**
 * Note that this action is somewhat naive and does not take into
 * account any hash loaded by a user or any user settings. It simply
 * attempts to find what the ultimate "parent" game is.
 */
class ResolveRootGameIdFromGameIdAction
{
    public function execute(int $gameId): int
    {
        // Get the game's achievement sets.
        $sets = GameAchievementSet::whereGameId($gameId)->get();
        if ($sets->isEmpty()) {
            return $gameId;
        }

        // For each achievement set, look for other games that reference this set.
        foreach ($sets as $set) {
            // Find all games that reference this achievement set.
            $linkedSets = GameAchievementSet::whereAchievementSetId($set->achievement_set_id)
                ->where('game_id', '!=', $gameId)
                ->get();

            // Look for a game that uses this set as a non-core type.
            $parentSet = $linkedSets->first(function ($linkedSet) {
                return $linkedSet->type !== AchievementSetType::Core;
            });

            if ($parentSet) {
                // We found a parent - return its game_id value.
                return $parentSet->game_id;
            }
        }

        // No parent found, this must be the root game.
        return $gameId;
    }
}
