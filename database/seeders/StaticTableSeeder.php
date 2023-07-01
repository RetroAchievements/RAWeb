<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Site\Models\StaticData;
use Illuminate\Database\Seeder;

class StaticTableSeeder extends Seeder
{
    public function run(): void
    {
        if (StaticData::count() > 0) {
            return;
        }

        StaticData::create([
            'NumAchievements' => 1,
            'NumAwarded' => 1,
            'NumGames' => 1,
            'NumRegisteredUsers' => 1,
            'TotalPointsEarned' => 1,
            'LastAchievementEarnedID' => 1,
            'LastAchievementEarnedByUser' => 1,
            'LastRegisteredUser' => 'nobody',
            'LastUpdatedGameID' => 1,
            'LastUpdatedAchievementID' => 1,
            'LastCreatedGameID' => 1,
            'LastCreatedAchievementID' => 1,
            'NextGameToScan' => 1,
            'Event_AOTW_AchievementID' => 1,
            'Event_AOTW_ForumID' => 1,
        ]);
    }
}
