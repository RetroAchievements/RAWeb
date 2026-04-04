<?php

declare(strict_types=1);

namespace App\Api\V2\Events;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Event;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Event $resource
 */
class EventResource extends BaseJsonApiResource
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
            'sortTitle' => $this->resource->legacyGame->sort_title,
            'badgeUrl' => $this->resource->badge_url,
            'state' => $this->resource->state->value,

            'playersTotal' => $this->resource->legacyGame->players_total,
            'achievementsPublished' => $this->resource->legacyGame->achievements_published,

            'activeFrom' => $this->resource->active_from,
            'activeThrough' => $this->resource->active_through,
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
            'awards' => $this->relation('awards')->withoutLinks(),

            // TODO add relationships
            // 'achievements' => $this->relation('achievements'),
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
