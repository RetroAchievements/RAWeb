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
        // Find this game's core achievement set.
        $coreSet = $game->gameAchievementSets()
            ->where('type', AchievementSetType::Core)
            ->select('achievement_set_id')
            ->first();

        if (!$coreSet) {
            return null;
        }

        // Check if this achievement set exists on another game as a non-core type.
        $backingGameSet = GameAchievementSet::where('achievement_set_id', $coreSet->achievement_set_id)
            ->where('type', '!=', AchievementSetType::Core)
            ->orderBy('created_at')
            ->select('game_id')
            ->first();

        if (!$backingGameSet) {
            return null;
        }

        if ($backingGameSet->game_id === $game->id) {
            return null;
        }

        return [
            'backingGameId' => $backingGameSet->game_id,
            'achievementSetId' => $coreSet->achievement_set_id,
        ];
    }
}
