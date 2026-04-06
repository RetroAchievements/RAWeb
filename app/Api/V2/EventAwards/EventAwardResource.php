<?php

declare(strict_types=1);

namespace App\Api\V2\EventAwards;

use App\Api\V2\BaseJsonApiResource;
use App\Models\EventAward;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property EventAward $resource
 */
class EventAwardResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'tierIndex' => $this->resource->tier_index,
            'label' => $this->resource->label,
            'pointsRequired' => $this->resource->points_required,
            'badgeUrl' => $this->resource->badge_url,
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
            'event' => $this->relation('event')->withoutLinks(),
        ];
    }

    /**
     * Get the resource's links.
     *
     * @param Request|null $request
     */
    public function links($request): Links
    {
        return new Links();
    }
}
