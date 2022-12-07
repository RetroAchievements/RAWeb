<?php

use Illuminate\Database\Migrations\Migration;
use RA\AchievementType;
use RA\AwardType;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';

    public function up()
    {
        $developerCountBoundaries = [
            100, 250, 500, 1000, 2500, 5000, 10000,
            25000, 50000, 100000, 250000, 500000,
            1000000, 2500000, 5000000,
        ];

        $developerPointBoundaries = [
            1000, 2500, 5000, 10000, 25000, 50000, 100000,
            250000, 500000, 1000000, 2500000, 5000000,
            10000000, 25000000, 50000000,
        ];

        $this->recalculateBadges($developerCountBoundaries, $developerPointBoundaries);
    }

    public function down()
    {
        $developerCountBoundaries = [
            5, 10, 50, 100, 200, 400, 600, 800,
            1000, 2000, 3000, 4000, 5000, 6000,
        ];

        $developerPointBoundaries = [
            100, 200, 300, 500, 800, 1000, 1500,
            2000, 3000, 4000, 5000, 10000, 15000,
            20000, 30000, 40000, 50000, 60000, 70000,
        ];

        $this->recalculateBadges($developerCountBoundaries, $developerPointBoundaries);
    }

    private function recalculateBadges(array $developerCountBoundaries, array $developerPointBoundaries): void
    {
        $users = DB::table('UserAccounts')
            ->select('User')
            ->where('ContribCount', '>', 0)
            ->get();

        foreach ($users as $user) {
            $points = 0;
            $count = 0;
            $pointLevel = 0;
            $countLevel = 0;

            $unlocks = DB::table('Awarded')
                ->leftJoin('Achievements', 'Achievements.ID', '=', 'Awarded.AchievementID')
                ->select('Awarded.Date', DB::raw('MAX(Awarded.HardcoreMode)'), 'Achievements.Points')
                ->where('Achievements.Author', '=', $user->User)
                ->where('Awarded.User', '!=', $user->User)
                ->where('Achievements.Flags', '=', AchievementType::OfficialCore)
                ->groupBy(['Awarded.User', 'Awarded.AchievementID'])
                ->orderBy('Awarded.Date')
                ->get();

            foreach ($unlocks as $unlock)
            {
                $count++;
                if ($count == $developerCountBoundaries[$countLevel]) {
                    DB::table('SiteAwards')
                        ->upsert([['User' => $user->User,
                                   'AwardType' => AwardType::AchievementUnlocksYield,
                                   'AwardData' => $countLevel, 'AwardDate' => $unlock->Date, ]],
                                 ['User', 'AwardType', 'AwardData'],
                                 ['AwardDate']);
                    $countLevel++;
                }

                $points += $unlock->Points;
                if ($points >= $developerPointBoundaries[$pointLevel]) {
                    DB::table('SiteAwards')
                        ->upsert([['User' => $user->User,
                                   'AwardType' => AwardType::AchievementPointsYield,
                                   'AwardData' => $pointLevel, 'AwardDate' => $unlock->Date, ]],
                                 ['User', 'AwardType', 'AwardData'],
                                 ['AwardDate']);
                    $pointLevel++;
                }
            }

            DB::table('SiteAwards')
                ->where('User', '=', $user->User)
                ->where('AwardType', '=', AwardType::AchievementUnlocksYield)
                ->where('AwardData', '>=', $countLevel)
                ->delete();

            DB::table('SiteAwards')
                ->where('User', '=', $user->User)
                ->where('AwardType', '=', AwardType::AchievementPointsYield)
                ->where('AwardData', '>=', $pointLevel)
                ->delete();

            DB::table('UserAccounts')
                ->where('User', '=', $user->User)
                ->update(['ContribCount' => $count, 'ContribYield' => $points]);
        }
    }
};
