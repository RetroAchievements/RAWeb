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
            // Core set fields.
            'coreAchievementsTotal' => $this->resource->achievements_total,
            'coreAchievementsUnlocked' => $this->resource->achievements_unlocked,
            'coreAchievementsUnlockedHardcore' => $this->resource->achievements_unlocked_hardcore,
            'coreAchievementsUnlockedSoftcore' => $this->resource->achievements_unlocked_softcore,
            'corePointsTotal' => $this->resource->points_total,
            'corePoints' => $this->resource->points,
            'corePointsHardcore' => $this->resource->points_hardcore,
            'corePointsWeighted' => $this->resource->points_weighted,

            // All sets fields.
            'achievementsTotal' => $this->resource->all_achievements_total,
            'achievementsUnlocked' => $this->resource->all_achievements_unlocked,
            'achievementsUnlockedHardcore' => $this->resource->all_achievements_unlocked_hardcore,
            'pointsTotal' => $this->resource->all_points_total,
            'points' => $this->resource->all_points,
            'pointsHardcore' => $this->resource->all_points_hardcore,
            'pointsWeighted' => $this->resource->all_points_weighted,

            // Completion.
            'completionPercentage' => $this->resource->completion_percentage,
            'completionPercentageHardcore' => $this->resource->completion_percentage_hardcore,

            // Timestamps.
            'lastPlayedAt' => $this->resource->last_played_at,
            'firstUnlockAt' => $this->resource->first_unlock_at,
            'lastUnlockAt' => $this->resource->last_unlock_at,
            'lastUnlockHardcoreAt' => $this->resource->last_unlock_hardcore_at,

            // Milestones.
            'beatenAt' => $this->resource->beaten_at,
            'beatenHardcoreAt' => $this->resource->beaten_hardcore_at,
            'coreCompletedAt' => $this->resource->completed_at,
            'coreCompletedHardcoreAt' => $this->resource->completed_hardcore_at,

            // Time tracking.
            'playtimeTotal' => $this->resource->playtime_total,
            'timeToBeat' => $this->resource->time_to_beat,
            'timeToBeatHardcore' => $this->resource->time_to_beat_hardcore,
            'timeTaken' => $this->resource->time_taken,
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
            'user' => $this->relation('user')->withoutLinks(),
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
