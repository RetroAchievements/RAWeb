<?php

namespace Database\Seeders\Legacy;

use Illuminate\Database\Seeder;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\StaticData;

class LegacyDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->seedSystems();
        $this->seedStaticData();
    }

    private function seedSystems()
    {
        if (System::count() > 0) {
            return;
        }

        /*
         * System IDs: https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
         */
        collect(config('systems'))->each(function ($systemData, $systemId) {
            System::create([
                'ID' => $systemId,
                'Name' => $systemData['name'],
            ]);
        });
    }

    private function seedStaticData()
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
