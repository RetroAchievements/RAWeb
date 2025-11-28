<?php

namespace App\Api\V2\Systems;

use App\Models\System;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property System $resource
 */
class SystemResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'name' => $this->resource->Name,
            'nameFull' => $this->resource->name_full,
            'nameShort' => $this->resource->name_short,
            'manufacturer' => $this->resource->manufacturer,
            'iconUrl' => $this->resource->icon_url,
            'active' => $this->resource->active,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        return [];
    }
}
