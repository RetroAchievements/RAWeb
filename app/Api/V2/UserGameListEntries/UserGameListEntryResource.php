<?php

declare(strict_types=1);

namespace App\Api\V2\UserGameListEntries;

use App\Api\V2\BaseJsonApiResource;
use App\Models\UserGameListEntry;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property UserGameListEntry $resource
 */
class UserGameListEntryResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'kind' => $this->resource->type->value,
            'createdAt' => $this->resource->created_at,
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
            'game' => $this->relation('game')->withoutLinks()->showDataIfLoaded(),
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
