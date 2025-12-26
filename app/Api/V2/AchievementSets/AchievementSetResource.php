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

            'type' => $this->getTypeFromPivot(),

            'gameIds' => $this->getGameIds(),
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
     * When accessed directly, finds the first GameAchievementSet with a title.
     */
    private function getTitleFromPivot(): ?string
    {
        // If pivot data exists (loaded via BelongsToMany relationship), use it.
        if (isset($this->resource->pivot)) {
            return $this->resource->pivot->title;
        }

        // Otherwise, find the first GameAchievementSet entity that has a title.
        // If there's not one, we'll return null.
        return $this->resource->gameAchievementSets()
            ->whereNotNull('title')
            ->first()
            ?->title;
    }

    /**
     * Get the achievement set type from the pivot when loaded via a Game relationship.
     * Returns null when the achievement set is accessed directly.
     */
    private function getTypeFromPivot(): ?string
    {
        if (!isset($this->resource->pivot)) {
            return null;
        }

        $type = $this->resource->pivot->type;
        if ($type instanceof AchievementSetType) {
            return $type->value;
        }

        return $type;
    }

    /**
     * Get the IDs of games this achievement set is linked to.
     * Excludes legacy "subset backing games" - games where the set is type=core
     * but also exists as non-core on another game.
     *
     * @return array<int>
     */
    private function getGameIds(): array
    {
        if (isset($this->resource->pivot)) {
            return [$this->resource->pivot->game_id];
        }

        $gameAchievementSets = $this->resource->gameAchievementSets;

        $hasCoreAttachment = $gameAchievementSets->contains(
            fn ($gas) => $gas->type === AchievementSetType::Core
        );
        $hasNonCoreAttachment = $gameAchievementSets->contains(
            fn ($gas) => $gas->type !== AchievementSetType::Core
        );

        // If it's attached as both core and non-core, exclude core (the subset backing game).
        if ($hasCoreAttachment && $hasNonCoreAttachment) {
            return $gameAchievementSets
                ->filter(fn ($gas) => $gas->type !== AchievementSetType::Core)
                ->pluck('game_id')
                ->values()
                ->all();
        }

        // Otherwise, include all linked games.
        return $gameAchievementSets
            ->pluck('game_id')
            ->values()
            ->all();
    }
}
