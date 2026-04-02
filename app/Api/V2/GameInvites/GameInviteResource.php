<?php

declare(strict_types=1);

namespace App\Api\V2\GameInvites;

use App\Api\V2\BaseJsonApiResource;
use App\Models\GameInvite;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property GameInvite $resource
 */
class GameInviteResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'message' => $this->resource->message,
            'status' => $this->resource->status->value,
            'sentAt' => $this->resource->sent_at,
            'respondedAt' => $this->resource->responded_at,
            'expiresAt' => $this->resource->expires_at,
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
            'sender' => $this->relation('sender')->withoutLinks(),
            'recipient' => $this->relation('recipient')->withoutLinks(),
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
