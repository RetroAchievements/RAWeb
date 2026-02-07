<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
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
        $parentGameId = $game->getParentGameIdAttribute();

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

        return [
            'backingGameId' => $parentGameId,
            'achievementSetId' => $coreSet->achievement_set_id,
        ];
    }
}
