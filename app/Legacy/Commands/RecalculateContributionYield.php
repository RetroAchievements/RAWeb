<?php

declare(strict_types=1);

namespace App\Legacy\Commands;

use App\Legacy\Models\PlayerBadge;
use App\Legacy\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RA\AchievementType;
use RA\AwardType;

class RecalculateContributionYield extends Command
{
    protected $signature = 'ra-legacy:community:developer:recalculate-contribution-yield {username?}';

    protected $description = 'Recalculates developer contributions and badge tiers';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $username = $this->argument('username');
        if (!empty($username)) {
            $this->recalculate($username);

            return;
        }

        $users = DB::connection('mysql_legacy')
            ->table('UserAccounts')
            ->select('User')
            ->where('ContribCount', '>', 0)
            ->get();

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        /** @var User $user */
        foreach ($users as $user) {
            $this->recalculate($user->User);
            $progressBar->advance();
        }

        $progressBar->finish();
    }

    private function recalculate(string $username): void
    {
        $points = 0;
        $pointLevel = 0;
        $nextPointThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);

        $count = 0;
        $countLevel = 0;
        $nextCountThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $countLevel);

        // get all unlocks for achievements created by the user ordered by date
        $unlocks = DB::connection('mysql_legacy')
            ->table('Awarded')
            ->leftJoin('Achievements', 'Achievements.ID', '=', 'Awarded.AchievementID')
            ->select('Awarded.Date', DB::raw('MAX(Awarded.HardcoreMode)'), 'Achievements.Points')
            ->where('Achievements.Author', '=', $username)
            ->where('Awarded.User', '!=', $username)
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
                    ->upsert([['User' => $username,
                            'AwardType' => AwardType::AchievementUnlocksYield,
                            'AwardData' => $countLevel, 'AwardDate' => $unlock->Date, ]],
                            ['User', 'AwardType', 'AwardData'],
                            ['AwardDate']);
                $countLevel++;

                $nextCountThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $countLevel);
            }

            $points += $unlock->Points;
            if ($points >= $nextPointThreshold) {
                DB::connection('mysql_legacy')
                    ->table('SiteAwards')
                    ->upsert([['User' => $username,
                            'AwardType' => AwardType::AchievementPointsYield,
                            'AwardData' => $pointLevel, 'AwardDate' => $unlock->Date, ]],
                            ['User', 'AwardType', 'AwardData'],
                            ['AwardDate']);
                $pointLevel++;

                $nextPointThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);
                if ($nextPointThreshold == 0) {
                    // if we run out of tiers, getBadgeThreshold returns 0, so everything will be >=. set to MAXINT
                    $nextPointThreshold = 0xFFFFFFFF;
                }
            }
        }

        // remove any extra badge tiers
        DB::connection('mysql_legacy')
            ->table('SiteAwards')
            ->where('User', '=', $username)
            ->where('AwardType', '=', AwardType::AchievementUnlocksYield)
            ->where('AwardData', '>=', $countLevel)
            ->delete();

        DB::connection('mysql_legacy')
            ->table('SiteAwards')
            ->where('User', '=', $username)
            ->where('AwardType', '=', AwardType::AchievementPointsYield)
            ->where('AwardData', '>=', $pointLevel)
            ->delete();

        // update the denormalized data
        DB::connection('mysql_legacy')
            ->table('UserAccounts')
            ->where('User', '=', $username)
            ->update(['ContribCount' => $count, 'ContribYield' => $points]);
    }
}
