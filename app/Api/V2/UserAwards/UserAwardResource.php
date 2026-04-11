<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Api\V2\BaseJsonApiResource;
use App\Models\PlayerBadge;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property PlayerBadge $resource
 */
class UserAwardResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $presenter = UserAwardPresenter::for($this->resource);

        return [
            'awardedAt' => $this->resource->awarded_at,
            'badgeUrl' => $presenter->badgeUrl(),
            'context' => $presenter->context(),
            'displayOrder' => $this->resource->order_column,
            'kind' => $presenter->kind(),
            'title' => $presenter->title(),
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        $relationships = [];
        $presenter = UserAwardPresenter::for($this->resource);

        if ($presenter->hasGameRelationship()) {
            $relationships['game'] = $this->relation('game', 'gameIfApplicable')
                ->withoutLinks()
                ->showDataIfLoaded();
        }

        if ($presenter->hasEventRelationship()) {
            $relationships['event'] = $this->relation('event', 'eventIfApplicable')
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
        // User awards are only exposed via the parent user relationship endpoint.
        return new Links();
    }
}
