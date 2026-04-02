<?php

declare(strict_types=1);

namespace App\Api\V2\MessageThreads;

use App\Api\V2\BaseJsonApiResource;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property MessageThread $resource
 */
class MessageThreadResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        /** @var User $user */
        $user = $request?->user();

        return [
            'title' => $this->resource->title,
            'numMessages' => $this->resource->num_messages,
            'unreadCount' => $this->resource->participants()
                ->where('user_id', $user?->id)
                ->first()?->num_unread ?? 0,
            'isUnread' => $this->resource->isUnread,
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
            'messages' => $this->relation('messages')->withoutLinks(),
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
