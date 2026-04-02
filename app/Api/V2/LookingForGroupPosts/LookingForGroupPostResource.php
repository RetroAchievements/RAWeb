<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupPosts;

use App\Api\V2\BaseJsonApiResource;
use App\Models\LookingForGroupPost;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property LookingForGroupPost $resource
 */
class LookingForGroupPostResource extends BaseJsonApiResource
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
            'note' => $this->resource->note,
            'maxPlayers' => $this->resource->max_players,
            'acceptedPlayersCount' => $this->resource->getAcceptedPlayersCount(),
            'availableSlotsCount' => $this->resource->getAvailableSlotsCount(),
            'status' => $this->resource->status->value,
            'scheduledFor' => $this->resource->scheduled_for,
            'expiresAt' => $this->resource->expires_at,
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
            'game' => $this->relation('game')->withoutLinks(),
            'creator' => $this->relation('creator')->withoutLinks(),
            'invites' => $this->relation('invites')->withoutLinks(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        $selfLink = $this->selfLink();

        return $selfLink ? new Links($selfLink) : new Links();
    }
}
