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
            'createdAt' => $this->resource->created_at,
            'gameId' => $this->resource->game_id,
            'gameTitle' => $this->resource->game->title,
            'gameIconUrl' => $this->resource->game->badge_url,
            'systemId' => $this->resource->game->system_id,
            'systemName' => $this->resource->game->system->name,
            'pointsTotal' => $this->resource->game->points_total,
            'achievementsPublished' => $this->resource->game->achievements_published,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        if (!$this->wasRelationshipIncluded($request, 'game')) {
            return [];
        }

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
