<?php

declare(strict_types=1);

namespace App\Api\V2\Games;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Game;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Game $resource
 */
class GameResource extends BaseJsonApiResource
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
            'sortTitle' => $this->resource->sort_title,

            'badgeUrl' => $this->resource->badge_url,
            'imageBoxArtUrl' => $this->resource->image_box_art_url,
            'imageTitleUrl' => $this->resource->image_title_url,
            'imageIngameUrl' => $this->resource->image_ingame_url,

            'releasedAt' => $this->resource->released_at,
            'releasedAtGranularity' => $this->resource->released_at_granularity?->value,

            'playersTotal' => $this->resource->players_total,
            'playersHardcore' => $this->resource->players_hardcore,

            'achievementsPublished' => $this->resource->achievements_published,
            'achievementsUnpublished' => $this->resource->achievements_unpublished,

            'pointsTotal' => $this->resource->points_total,
            'pointsWeighted' => $this->resource->points_weighted,

            'timesBeaten' => $this->resource->times_beaten,
            'timesBeatenHardcore' => $this->resource->times_beaten_hardcore,
            'medianTimeToBeatMinutes' => $this->resource->median_time_to_beat,
            'medianTimeToBeatHardcoreMinutes' => $this->resource->median_time_to_beat_hardcore,
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
            'system' => $this->relation('system')->withoutLinks(),
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
