<?php

namespace App\Api\V2\AchievementSetVersions;

use App\Api\V2\BaseJsonApiResource;
use App\Models\AchievementSetVersion;
use Illuminate\Http\Request;

/**
 * @property AchievementSetVersion $resource
 */
class AchievementSetVersionResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'version' => $this->resource->version,

            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,

            'definition' => $this->resource->definition,
            'playersTotal' => $this->resource->players_total,
            'playersHardcore' => $this->resource->players_hardcore,
            'achievementsPublished' => $this->resource->achievements_published,
            'achievementsUnpublished' => $this->resource->achievements_unpublished,
            'pointsTotal' => $this->resource->points_total,

            'achievementSetId' => $this->resource->achievement_set_id,
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
            'achievementSets' => $this->relation('achievementSet')
                ->withoutLinks(),
        ];
    }
}
