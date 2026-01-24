<?php

declare(strict_types=1);

namespace App\Api\V2\Hubs;

use App\Api\V2\BaseJsonApiResource;
use App\Models\GameSet;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property GameSet $resource
 */
class HubResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $areCountsLoaded = isset($this->resource->games_count)
            && isset($this->resource->child_hubs_count)
            && isset($this->resource->parent_hubs_count);
        if (!$areCountsLoaded) {
            $this->resource->loadCount(['games', 'children as child_hubs_count', 'parents as parent_hubs_count']);
        }

        return [
            'title' => $this->resource->title,
            'sortTitle' => $this->resource->sort_title,
            'badgeUrl' => $this->resource->badge_url,

            'hasMatureContent' => $this->resource->has_mature_content,

            'gamesCount' => $this->resource->games_count,
            'childHubsCount' => $this->resource->child_hubs_count,
            'parentHubsCount' => $this->resource->parent_hubs_count,
            'isEventHub' => $this->resource->is_event_hub,

            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
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
            'games' => $this->relation('games')->withoutLinks(),
            'parents' => $this->relation('parents')->withoutLinks(),
            'children' => $this->relation('children')->withoutLinks(),
        ];
    }

    /**
     * Get the resource's links.
     *
     * @param Request|null $request
     */
    public function links($request): Links
    {
        $links = [
            $this->selfLink(),
        ];

        if ($this->resource->forum_topic_id) {
            $links[] = new Link(
                'forumTopic',
                route('forum-topic.show', ['topic' => $this->resource->forum_topic_id])
            );
        }

        return new Links(...$links);
    }
}
