<?php

declare(strict_types=1);

namespace App\Api\V2\GameHashes;

use App\Api\V2\BaseJsonApiResource;
use App\Models\GameHash;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property GameHash $resource
 */
class GameHashResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'raMd5' => $this->resource->md5,
            'name' => $this->resource->name,
            'labels' => array_values(array_filter(explode(',', $this->resource->labels ?? ''))),
            'compatibility' => $this->resource->compatibility?->value,
            'patchUrl' => $this->resource->patch_url,

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
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        return new Links();
    }
}
