<?php

declare(strict_types=1);

namespace App\Api\V2\Leaderboards;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Leaderboard $resource
 */
class LeaderboardResource extends BaseJsonApiResource
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

            'format' => $this->resource->format,
            'rankAsc' => $this->resource->rank_asc,

            'state' => $this->resource->state?->value,
            'orderColumn' => $this->resource->order_column,

            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
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
            'game' => $this->relation('game')->withoutLinks(),
            'developer' => $this->relation('developer')->withoutLinks(),
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
}
