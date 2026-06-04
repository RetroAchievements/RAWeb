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
            'games' => $this->relation('game')
                ->withoutLinks()
                ->showDataIfLoaded()
        ];
    }

}
