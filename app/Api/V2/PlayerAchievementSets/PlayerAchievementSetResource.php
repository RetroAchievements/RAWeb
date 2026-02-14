<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievementSets;

use App\Api\V2\BaseJsonApiResource;
use App\Models\PlayerAchievementSet;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property PlayerAchievementSet $resource
 */
class PlayerAchievementSetResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'achievementsUnlocked' => $this->resource->achievements_unlocked,
            'achievementsUnlockedHardcore' => $this->resource->achievements_unlocked_hardcore,
            'achievementsUnlockedSoftcore' => $this->resource->achievements_unlocked_softcore,

            'points' => $this->resource->points,
            'pointsHardcore' => $this->resource->points_hardcore,
            'pointsWeighted' => $this->resource->points_weighted,

            'completionPercentage' => $this->resource->completion_percentage,
            'completionPercentageHardcore' => $this->resource->completion_percentage_hardcore,

            'lastUnlockAt' => $this->resource->last_unlock_at,
            'lastUnlockHardcoreAt' => $this->resource->last_unlock_hardcore_at,
            'completedAt' => $this->resource->completed_at,
            'completedHardcoreAt' => $this->resource->completed_hardcore_at,

            'timeTaken' => $this->resource->time_taken,
            'timeTakenHardcore' => $this->resource->time_taken_hardcore,

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
            'achievementSet' => $this->relation('achievementSet')->withoutLinks(),
            'game' => $this->relation('game')->withoutLinks(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        // Player achievement sets have no dedicated web URL.
        return new Links();
    }
}
