<?php

declare(strict_types=1);

namespace App\Api\V2\EventAchievements;

use App\Api\V2\BaseJsonApiResource;
use App\Models\EventAchievement;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property EventAchievement $resource
 */
class EventAchievementResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $achievement = $this->resource->achievement;

        return [
            'activeFrom' => $this->resource->active_from,
            'activeUntil' => $this->resource->active_until,
            'decorator' => $this->resource->decorator,

            'achievementTitle' => $achievement->title,
            'achievementDescription' => $achievement->description,
            'achievementPoints' => $achievement->points,
            'achievementBadgeUrl' => $achievement->badge_url,
            'achievementBadgeLockedUrl' => $achievement->badge_locked_url,
            'eventTitle' => $this->resource->event?->title,
            'eventBadgeUrl' => $this->resource->event?->badge_url,
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
            'event' => $this->relation('event')->withoutLinks()->showDataIfLoaded(),
            'sourceAchievement' => $this->relation('sourceAchievement')->withoutLinks()->showDataIfLoaded(),
            'eventAchievement' => $this->relation('eventAchievement', 'achievement')->withoutLinks()->showDataIfLoaded(),
        ];
    }

    /**
     * Get the resource's links.
     *
     * @param Request|null $request
     */
    public function links($request): Links
    {
        $webLink = new Link('webUrl', route('achievement.show', ['achievement' => $this->resource->achievement_id]));

        return new Links(...array_filter([$this->selfLink(), $webLink]));
    }
}
