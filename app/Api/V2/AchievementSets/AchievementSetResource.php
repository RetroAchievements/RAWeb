<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSets;

use App\Api\V2\BaseJsonApiResource;
use App\Models\AchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property AchievementSet $resource
 */
class AchievementSetResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'title' => $this->getTitleFromPivot(),

            'pointsTotal' => $this->resource->points_total,
            'pointsWeighted' => $this->resource->points_weighted,

            'achievementsPublished' => $this->resource->achievements_published,
            'achievementsUnpublished' => $this->resource->achievements_unpublished,

            'badgeUrl' => $this->resource->image_asset_path_url,

            'achievementsFirstPublishedAt' => $this->resource->achievements_first_published_at,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,

            'types' => $this->getGameTypes(),
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
            'games' => $this->relation('games', 'linkedGames')->withoutLinks(),
        ];
    }

    /**
     * Get the resource's links.
     *
     * @param Request|null $request
     */
    public function links($request): Links
    {
        return new Links(
            $this->selfLink(),
        );
    }

    /**
     * Get the title from the GameAchievementSet pivot.
     * When accessed via a Game relationship, uses the pivot's title.
     * When accessed directly (or via Achievement relationship), finds the first GameAchievementSet with a title.
     */
    private function getTitleFromPivot(): ?string
    {
        // If pivot data exists and has a title column (game_achievement_sets pivot), use it.
        // Note: When accessed via Achievement->achievementSets, the pivot is
        // achievement_set_achievements which doesn't have a title column.
        if (isset($this->resource->pivot)) {
            $pivotAttributes = $this->resource->pivot->getAttributes();
            if (array_key_exists('title', $pivotAttributes)) {
                return $this->resource->pivot->title;
            }
        }

        // Otherwise, find the first GameAchievementSet entity that has a title.
        // If there's not one, we'll return null.
        return $this->resource->gameAchievementSets()
            ->whereNotNull('title')
            ->first()
            ?->title;
    }

    /**
     * Get game/type pairs for this achievement set.
     * Excludes subset backing games (where set is core but also exists as non-core elsewhere).
     *
     * @return array<array{gameId: int, type: string}>
     */
    private function getGameTypes(): array
    {
        // When accessed via a Game relationship (game_achievement_sets pivot), return just that game's context.
        // Note: When accessed via Achievement->achievementSets, the pivot is
        // achievement_set_achievements which doesn't have game_id or type columns.
        if (isset($this->resource->pivot)) {
            $pivotAttributes = $this->resource->pivot->getAttributes();
            if (array_key_exists('game_id', $pivotAttributes)) {
                $type = $this->resource->pivot->type;

                return [[
                    'gameId' => $this->resource->pivot->game_id,
                    'type' => $type instanceof AchievementSetType ? $type->value : $type,
                ]];
            }
        }

        // When accessed directly (or via an Achievement relationship), return all game/type pairs (excluding backing games).
        $gameAchievementSets = $this->resource->gameAchievementSets;

        $hasCoreAttachment = $gameAchievementSets->contains(
            fn ($gas) => $gas->type === AchievementSetType::Core
        );
        $hasNonCoreAttachment = $gameAchievementSets->contains(
            fn ($gas) => $gas->type !== AchievementSetType::Core
        );

        // If attached as both core and non-core, exclude core (the subset backing game).
        $setsToInclude = ($hasCoreAttachment && $hasNonCoreAttachment)
            ? $gameAchievementSets->filter(fn ($gas) => $gas->type !== AchievementSetType::Core)
            : $gameAchievementSets;

        return $setsToInclude->map(fn ($gas) => [
            'gameId' => $gas->game_id,
            'type' => $gas->type instanceof AchievementSetType ? $gas->type->value : $gas->type,
        ])->values()->all();
    }
}
