<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerGames;

use App\Api\V2\BaseJsonApiResource;
use App\Models\PlayerGame;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property PlayerGame $resource
 */
class PlayerGameResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            // Timestamps.
            'lastPlayedAt' => $this->resource->last_played_at,
            'firstUnlockAt' => $this->resource->first_unlock_at,
            'lastUnlockAt' => $this->resource->last_unlock_at,
            'lastUnlockHardcoreAt' => $this->resource->last_unlock_hardcore_at,

            // Milestones.
            'beatenAt' => $this->resource->beaten_at,
            'beatenHardcoreAt' => $this->resource->beaten_hardcore_at,

            // Time tracking.
            'playtimeTotalSeconds' => $this->resource->playtime_total,
            'timeToBeatSeconds' => $this->resource->time_to_beat,
            'timeToBeatHardcoreSeconds' => $this->resource->time_to_beat_hardcore,
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
            'achievementSets' => $this->relation('achievementSets')->withoutLinks(),
            'game' => $this->relation('game')->withoutLinks(),
            'playerAchievementSets' => $this->relation('playerAchievementSets')->withoutLinks(),
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
