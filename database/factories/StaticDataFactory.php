<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Site\Models\StaticData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaticData>
 */
class StaticDataFactory extends Factory
{
    protected $model = StaticData::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'NumAchievements' => 1,
            'NumAwarded' => 1,
            'NumGames' => 1,
            'NumRegisteredUsers' => 1,
            'TotalPointsEarned' => 1,
            'LastAchievementEarnedID' => 1,
            'LastAchievementEarnedByUser' => 1,
            'LastRegisteredUser' => 1,
            'LastUpdatedGameID' => 1,
            'LastUpdatedAchievementID' => 1,
            'LastCreatedGameID' => 1,
            'LastCreatedAchievementID' => 1,
            'NextGameToScan' => 1,
            'NextUserIDToScan' => 1,
            'Event_AOTW_AchievementID' => 1,
            'Event_AOTW_ForumID' => 1,
        ];
    }
}
