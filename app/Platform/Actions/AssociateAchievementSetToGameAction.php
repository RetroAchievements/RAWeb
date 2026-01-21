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
        $achievementSet = $sourceGame->achievementSets()
            ->wherePivot('type', AchievementSetType::Core->value)
            ->first();

        if ($this->achievementSetAlreadyExists($targetGame, $achievementSet)) {
            throw new InvalidArgumentException("Achievement set is already associated with the game.");
        }

        // Specialty sets can only be linked to one parent game.
        if ($this->isSpecialtyType($type) && !$achievementSet->canBeLinkedAsSpecialtyTo($targetGame)) {
            throw new InvalidArgumentException(
                "Specialty sets cannot be linked to multiple parent games. This set is already linked to another game."
            );
        }

        // If the set is already linked as specialty somewhere, it can't be linked elsewhere
        // (even as bonus), because specialty requires unique hashes.
        if ($achievementSet->isLinkedAsSpecialtyElsewhere($targetGame)) {
            throw new InvalidArgumentException(
                "This set is already linked as a Specialty set to another game and cannot be linked elsewhere."
            );
        }

        $this->associateAchievementSet($targetGame, $achievementSet, $type, $title);
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
        string $title,
    ): void {
        $game->achievementSets()->attach($achievementSet->id, [
            'type' => $type->value,
            'order_column' => ($game->achievementSets()->max('order_column') ?? 0) + 1,
            'title' => $title,
        ]);
    }

    private function isSpecialtyType(AchievementSetType $type): bool
    {
        return in_array($type, [AchievementSetType::Specialty, AchievementSetType::WillBeSpecialty], true);
    }
}
