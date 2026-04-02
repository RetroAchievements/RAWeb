<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupInvites;

use App\Api\V2\BaseJsonApiResource;
use App\Models\LookingForGroupInvite;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property LookingForGroupInvite $resource
 */
class LookingForGroupInviteResource extends BaseJsonApiResource
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
            'lookingForGroupPost' => $this->relation('lookingForGroupPost')->withoutLinks(),
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
