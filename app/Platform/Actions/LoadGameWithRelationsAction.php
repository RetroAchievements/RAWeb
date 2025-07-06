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
     * @param AchievementFlag $flag the achievement flag to filter by
     * @param int|null $targetAchievementSetId if provided, only load this specific achievement set
     * @return Game the game with properly loaded relations
     */
    public function execute(Game $game, AchievementFlag $flag, ?int $targetAchievementSetId = null): Game
    {
        $game->loadMissing([
            'gameAchievementSets' => function ($query) use ($targetAchievementSetId) {
                if ($targetAchievementSetId !== null) {
                    // If a specific achievement set is requested, only load that one.
                    $query->where('achievement_set_id', $targetAchievementSetId);
                } else {
                    // Otherwise, only load core sets.
                    // We won't rule out the possibility a game has multiple core sets,
                    // though this should be exceedingly rare (if ever).
                    $query->where('type', AchievementSetType::Core);
                }
            },
            'hashes',
            'hubs' => function ($query) {
                $query->with(['children', 'viewRoles']);
            },
            'releases',
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

            'achievementSet.achievements.authorshipCredits.user',
            'achievementSet.achievements.developer',
            'achievementSet.achievements.activeMaintainer.user',
            'achievementSet.achievementSetAuthors.user',
        ]);

        // Load all selectable achievement sets for navigation purposes only.
        // We'll pass this along as a custom attribute for the props building action.
        $game->setAttribute('selectableGameAchievementSets', $game->selectableGameAchievementSets()->get());

        return $game;
    }
}
