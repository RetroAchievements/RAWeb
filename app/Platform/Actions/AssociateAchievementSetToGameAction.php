<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Platform\Enums\AchievementSetType;
use InvalidArgumentException;

/**
 * Associates an achievement set from one game with another game.
 * This is typically used to create a multiset link, as subsets have their
 * own unique game IDs.
 */
class AssociateAchievementSetToGameAction
{
    public function execute(Game $targetGame, Game $sourceGame, AchievementSetType $type, string $title): void
    {
        $legacySubsetAchievementSet = $sourceGame->achievementSets()
            ->wherePivot('type', AchievementSetType::Core->value)
            ->first();

        if ($this->achievementSetAlreadyExists($targetGame, $legacySubsetAchievementSet)) {
            throw new InvalidArgumentException("Achievement set is already associated with the game.");
        }

        $this->associateAchievementSet(
            $targetGame,
            $legacySubsetAchievementSet,
            $type,
            $title
        );
    }

    private function achievementSetAlreadyExists(Game $game, AchievementSet $achievementSet): bool
    {
        return $game->achievementSets()
            ->wherePivot('achievement_set_id', $achievementSet->id)
            ->exists();
    }

    private function associateAchievementSet(
        Game $game,
        AchievementSet $achievementSet,
        AchievementSetType $type,
        string $title
    ): void {
        $game->achievementSets()->attach($achievementSet->id, [
            'type' => $type->value,
            'order_column' => ($game->achievementSets()->max('order_column') ?? 0) + 1,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
