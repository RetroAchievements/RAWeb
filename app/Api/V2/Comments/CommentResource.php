<?php

declare(strict_types=1);

namespace App\Api\V2\Comments;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Comment $resource
 */
class CommentResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'authorDisplayName' => $this->resource->getAttribute('author_display_name') ?? $this->resource->user->display_name,
            'authorId' => $this->resource->getAttribute('author_ulid') ?? $this->resource->user->ulid,
            'body' => $this->resource->body,
            'submittedAt' => $this->resource->created_at,
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
            'author' => $this->relation('author', 'user')->withoutLinks()->showDataIfLoaded(),
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
