<?php

declare(strict_types=1);

namespace App\Api\V2\Achievements;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Achievement;
use App\Models\AchievementSetAchievement;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Achievement $resource
 */
class AchievementResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'title' => $this->resource->title,
            'description' => $this->resource->description,

            'points' => $this->resource->points,
            'pointsWeighted' => $this->resource->points_weighted,

            'badgeUrl' => $this->resource->badge_url,
            'badgeLockedUrl' => $this->resource->badge_locked_url,

            'type' => $this->resource->type,

            'state' => $this->resource->is_promoted ? 'promoted' : 'unpromoted',

            'orderColumn' => $this->getOrderColumnFromPivot(),

            'unlocksTotal' => $this->resource->unlocks_total,
            'unlocksHardcore' => $this->resource->unlocks_hardcore,
            'unlockPercentage' => $this->resource->unlock_percentage,
            'unlockHardcorePercentage' => $this->resource->unlock_hardcore_percentage,

            'createdAt' => $this->resource->created_at,
            'modifiedAt' => $this->resource->modified_at,
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
            'developer' => $this->relation('developer')->withoutLinks(),
            'achievementSet' => $this->relation('achievementSet')->withoutLinks(),
            'games' => $this->relation('games')->withoutLinks(),
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
     * Get the order column from the achievement_set_achievements pivot.
     * Since an achievement only belongs to one set, we query the pivot table directly.
     */
    private function getOrderColumnFromPivot(): ?int
    {
        return AchievementSetAchievement::where('achievement_id', $this->resource->id)
            ->value('order_column');
    }
}
