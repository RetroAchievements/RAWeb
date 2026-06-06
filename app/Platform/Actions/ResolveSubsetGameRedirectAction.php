<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;

class ResolveSubsetGameRedirectAction
{
    /**
     * Checks if a game is a "subset game" that should redirect to its parent game.
     *
     * A "subset game" is one whose core achievement set is linked as a non-core type
     * (bonus, specialty, etc) on another game. When this is the case, we want to
     * redirect users to the parent game with the ?set= parameter.
     *
     * @return array{backingGameId: int, achievementSetId: int}|null
     */
    public function execute(Game $game): ?array
    {
        $parentGameId = $game->parent_game_id;

        if (!$parentGameId || $parentGameId === $game->id) {
            return null;
        }

        $coreSet = $game->gameAchievementSets()
            ->where('type', AchievementSetType::Core)
            ->select('achievement_set_id')
            ->first();

        if (!$coreSet) {
            return null;
        }

        // Only redirect if the parent actually has this achievement set linked.
        // Without this, title-based parent detection can redirect to a parent
        // that doesn't know about the subset's achievement set, creating a
        // broken redirect loop.
        $parentHasSet = GameAchievementSet::where('game_id', $parentGameId)
            ->where('achievement_set_id', $coreSet->achievement_set_id)
            ->exists();

        if (!$parentHasSet) {
            return null;
        }

        return [
            'backingGameId' => $parentGameId,
            'achievementSetId' => $coreSet->achievement_set_id,
        ];
    }
}
