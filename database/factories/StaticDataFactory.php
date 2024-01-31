<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StaticData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
            'num_hardcore_mastery_awards' => 1,
            'num_hardcore_game_beaten_awards' => 1,
            'last_game_hardcore_mastered_game_id' => 1,
            'last_game_hardcore_mastered_user_id' => 1,
            'last_game_hardcore_mastered_at' => Carbon::now()->subMinutes(5),
            'last_game_hardcore_beaten_game_id' => 1,
            'last_game_hardcore_beaten_user_id' => 1,
            'last_game_hardcore_beaten_at' => Carbon::now()->subMinutes(5),
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
