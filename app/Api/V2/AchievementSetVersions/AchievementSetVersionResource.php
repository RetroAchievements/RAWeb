<?php

namespace App\Api\V2\AchievementSetVersions;

use App\Models\AchievementSetVersion;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property AchievementSetVersion $resource
 */
class AchievementSetVersionResource extends JsonApiResource
{

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'version' => $this->resource->version,
            'definition' => $this->resource->definition,
            'players_total' => $this->resource->players_total,
            'players_hardcore' => $this->resource->players_hardcore,
            'achievements_published' => $this->resource->achievements_published,
            'achievements_unpublished' => $this->resource->achievements_unpublished,
            'points_total' => $this->resource->points_total,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            'achievementSets' => $this->relation('achievementSet')
                ->withoutLinks()
                ->showDataIfLoaded()
        ];
    }

}
