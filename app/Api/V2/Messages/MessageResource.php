<?php

declare(strict_types=1);

namespace App\Api\V2\Messages;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Message;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Message $resource
 */
class MessageResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'body' => $this->resource->body,
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
            'author' => $this->relation('author')->withoutLinks(),
            'sentBy' => $this->relation('sentBy')->withoutLinks()->showDataIfLoaded(),
            'messageThread' => $this->relation('messageThread')->withoutLinks(),
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
