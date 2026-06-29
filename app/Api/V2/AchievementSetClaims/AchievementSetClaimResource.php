<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims;

use App\Api\V2\BaseJsonApiResource;
use App\Models\AchievementSetClaim;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property AchievementSetClaim $resource
 */
class AchievementSetClaimResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $presenter = AchievementSetClaimPresenter::for($this->resource);

        return [
            'claimedAt' => $this->resource->created_at,
            'finishedAt' => $this->resource->finished_at,
            'updatedAt' => $this->resource->updated_at,

            'status' => $presenter->status(),
            'claimType' => $presenter->claimType(),
            'setType' => $presenter->setType(),
            'specialType' => $presenter->specialType(),

            'extensionsCount' => $this->resource->extensions_count,
            'minutesLeft' => $presenter->minutesLeft(),

            // embedded context so consumers can render lists without a forced ?include
            'userId' => $presenter->userId(),
            'userDisplayName' => $presenter->userDisplayName(),
            'gameId' => $presenter->gameId(),
            'gameTitle' => $presenter->gameTitle(),
            'gameIconUrl' => $presenter->gameIconUrl(),
            'systemId' => $presenter->systemId(),
            'systemName' => $presenter->systemName(),
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * Relationship `data` is only emitted when the consumer explicitly opts in
     * via `?include=...`, matching the convention established by user-awards.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        $relationships = [];

        if ($this->wasRelationshipIncluded($request, 'user')) {
            $relationships['user'] = $this->relation('user')
                ->withoutLinks()
                ->showDataIfLoaded();
        }

        if ($this->wasRelationshipIncluded($request, 'game')) {
            $relationships['game'] = $this->relation('game')
                ->withoutLinks()
                ->showDataIfLoaded();
        }

        return $relationships;
    }

    /**
     * Get the resource's links.
     *
     * @param Request|null $request
     */
    public function links($request): Links
    {
        // Claims have no standalone show route, so suppress the self link.
        return new Links();
    }
}
