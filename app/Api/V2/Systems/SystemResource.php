<?php

namespace App\Api\V2\Systems;

use App\Api\V2\BaseJsonApiResource;
use App\Models\System;
use Illuminate\Http\Request;

/**
 * @property System $resource
 */
class SystemResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'name' => $this->resource->name,
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
