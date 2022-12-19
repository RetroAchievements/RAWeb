<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Models\Badge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RA\AchievementType;
use RA\AwardType;

class RecalcContribYield extends Command
{
    protected $signature = 'ra:platform:developer:recalc-contrib-yield {user?}';
    protected $description = 'Recalculates developer contributions and badge tiers';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $user = $this->argument('user');
        if (!empty($user)) {
            self::recalculate($user);
        } else {
            $users = DB::connection('mysql_legacy')
                ->table('UserAccounts')
                ->select('User')
                ->where('ContribCount', '>', 0)
                ->get();

            $progressBar = $this->output->createProgressBar($users->count());
            $progressBar->start();

            foreach ($users as $user) {
                self::recalculate($user->User);
                $progressBar->advance();
            }

            $progressBar->finish();
        }
    }

    private static function recalculate(string $user): void
    {
        $points = 0;
        $pointLevel = 0;
        $nextPointThreshold = Badge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);

        $count = 0;
        $countLevel = 0;
        $nextCountThreshold = Badge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $countLevel);

        // get all unlocks for achievements created by the user ordered by date
        $unlocks = DB::connection('mysql_legacy')
            ->table('Awarded')
            ->leftJoin('Achievements', 'Achievements.ID', '=', 'Awarded.AchievementID')
            ->select('Awarded.Date', DB::raw('MAX(Awarded.HardcoreMode)'), 'Achievements.Points')
            ->where('Achievements.Author', '=', $user)
            ->where('Awarded.User', '!=', $user)
            ->where('Achievements.Flags', '=', AchievementType::OfficialCore)
            ->groupBy(['Awarded.User', 'Awarded.AchievementID'])
            ->orderBy('Awarded.Date')
            ->get();

        foreach ($unlocks as $unlock)
        {
            // when a threshold is crossed, award a badge
            $count++;
            if ($count === $nextCountThreshold) {
                DB::connection('mysql_legacy')
                    ->table('SiteAwards')
                    ->upsert([['User' => $user,
                            'AwardType' => AwardType::AchievementUnlocksYield,
                            'AwardData' => $countLevel, 'AwardDate' => $unlock->Date, ]],
                            ['User', 'AwardType', 'AwardData'],
                            ['AwardDate']);
                $countLevel++;

                $nextCountThreshold = Badge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $countLevel);
            }

            $points += $unlock->Points;
            if ($points >= $nextPointThreshold) {
                DB::connection('mysql_legacy')
                    ->table('SiteAwards')
                    ->upsert([['User' => $user,
                            'AwardType' => AwardType::AchievementPointsYield,
                            'AwardData' => $pointLevel, 'AwardDate' => $unlock->Date, ]],
                            ['User', 'AwardType', 'AwardData'],
                            ['AwardDate']);
                $pointLevel++;

                $nextPointThreshold = Badge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);
                if ($nextPointThreshold == 0) {
                    // if we run out of tiers, getBadgeThreshold returns 0, so everything will be >=. set to MAXINT
                    $nextPointThreshold = 0xFFFFFFFF;
                }
            }
        }

        // remove any extra badge tiers
        DB::connection('mysql_legacy')
            ->table('SiteAwards')
            ->where('User', '=', $user)
            ->where('AwardType', '=', AwardType::AchievementUnlocksYield)
            ->where('AwardData', '>=', $countLevel)
            ->delete();

        DB::connection('mysql_legacy')
            ->table('SiteAwards')
            ->where('User', '=', $user)
            ->where('AwardType', '=', AwardType::AchievementPointsYield)
            ->where('AwardData', '>=', $pointLevel)
            ->delete();

        // update the denormalized data
        DB::connection('mysql_legacy')
            ->table('UserAccounts')
            ->where('User', '=', $user)
            ->update(['ContribCount' => $count, 'ContribYield' => $points]);
    }
}
