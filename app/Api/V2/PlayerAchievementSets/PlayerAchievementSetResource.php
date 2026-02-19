<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievementSets;

use App\Api\V2\BaseJsonApiResource;
use App\Models\PlayerAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property PlayerAchievementSet $resource
 */
class PlayerAchievementSetResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'achievementsUnlocked' => $this->resource->achievements_unlocked,
            'achievementsUnlockedHardcore' => $this->resource->achievements_unlocked_hardcore,

            'points' => $this->resource->points,
            'pointsHardcore' => $this->resource->points_hardcore,
            'pointsWeighted' => $this->resource->points_weighted,

            'completionPercentage' => $this->resource->completion_percentage,
            'completionPercentageHardcore' => $this->resource->completion_percentage_hardcore,

            'lastUnlockAt' => $this->resource->last_unlock_at,
            'lastUnlockHardcoreAt' => $this->resource->last_unlock_hardcore_at,
            'completedAt' => $this->resource->completed_at,
            'completedHardcoreAt' => $this->resource->completed_hardcore_at,

            'timeTakenSeconds' => $this->resource->time_taken,
            'timeTakenHardcoreSeconds' => $this->resource->time_taken_hardcore,

            'setContext' => $this->getSetContext(),
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        return [
            'achievementSet' => $this->relation('achievementSet')->withoutLinks(),
            'game' => $this->relation('game')->withoutLinks(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        // Player achievement sets have no dedicated web URL.
        return new Links();
    }

    /**
     * Get game/type pairs so callers know the game context
     * without needing to include the full game resource.
     *
     * @return array<array{gameId: int, type: string}>
     */
    private function getSetContext(): array
    {
        $gameAchievementSets = $this->resource->achievementSet->gameAchievementSets;

        $hasCoreAttachment = $gameAchievementSets->contains(
            fn ($gas) => $gas->type === AchievementSetType::Core
        );
        $hasNonCoreAttachment = $gameAchievementSets->contains(
            fn ($gas) => $gas->type !== AchievementSetType::Core
        );

        $setsToInclude = ($hasCoreAttachment && $hasNonCoreAttachment)
            ? $gameAchievementSets->filter(fn ($gas) => $gas->type !== AchievementSetType::Core)
            : $gameAchievementSets;

        return $setsToInclude->map(fn ($gas) => [
            'achievementSetId' => $gas->achievement_set_id,
            'gameId' => $gas->game_id,
            'type' => $gas->type instanceof AchievementSetType ? $gas->type->value : $gas->type,
        ])->values()->all();
    }
}
