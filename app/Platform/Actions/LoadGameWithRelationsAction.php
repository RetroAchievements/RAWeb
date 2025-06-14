<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;

class LoadGameWithRelationsAction
{
    /**
     * Efficiently load a game for the game show page with all its required relations.
     *
     * @param Game $game the game to load relations for
     * @return Game the game with properly loaded relations
     */
    public function execute(Game $game, AchievementFlag $flag): Game
    {
        $excludeSetTypes = [
            AchievementSetType::WillBeBonus->value,
            AchievementSetType::WillBeSpecialty->value,
            AchievementSetType::WillBeExclusive->value,
        ];

        $game->loadMissing([
            'gameAchievementSets' => function ($query) use ($excludeSetTypes) {
                $query->whereNotIn('type', $excludeSetTypes);
            },
            'hashes',
            'hubs' => function ($query) {
                $query->with(['children', 'viewRoles']);
            },
            'visibleComments' => function ($query) {
                $query->latest('Submitted')
                    ->limit(20)
                    ->with(['user' => function ($userQuery) {
                        $userQuery->withTrashed();
                    }]);
            },
        ]);

        // Then load the related achievements for the filtered sets.
        $game->gameAchievementSets->load([
            'achievementSet.achievements' => function ($query) use ($flag) {
                $query->where('Flags', $flag->value);
            },

            'achievementSet.achievements.developer',
        ]);

        return $game;
    }
}
