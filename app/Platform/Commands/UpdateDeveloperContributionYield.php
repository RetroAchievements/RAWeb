<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlags;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateDeveloperContributionYield extends Command
{
    protected $signature = 'ra:platform:update-developer-contribution-yield {username?}';
    protected $description = 'Calculate developer contributions and badge tiers';

    public function handle(): void
    {
        $username = $this->argument('username');
        if (!empty($username)) {
            $this->calculate($username);

            return;
        }

        $users = User::select('User')
            ->where('ContribCount', '>', 0)
            ->get();

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        /** @var User $user */
        foreach ($users as $user) {
            $this->calculate($user->User);
            $progressBar->advance();
        }

        $progressBar->finish();
    }

    private function calculate(string $username): void
    {
        $points = 0;
        $pointLevel = 0;
        $nextPointThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);

        $count = 0;
        $countLevel = 0;
        $nextCountThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $countLevel);

        // get all unlocks for achievements created by the user ordered by date
        $unlocks = PlayerAchievementLegacy::select('Awarded.Date', DB::raw('MAX(Awarded.HardcoreMode)'), 'Achievements.Points')
            ->leftJoin('Achievements', 'Achievements.ID', '=', 'Awarded.AchievementID')
            ->where('Achievements.Author', '=', $username)
            ->where('Awarded.User', '!=', $username)
            ->where('Achievements.Flags', '=', AchievementFlags::OfficialCore)
            ->groupBy(['Awarded.User', 'Awarded.AchievementID'])
            ->orderBy('Awarded.Date')
            ->get();

        /** @var PlayerAchievementLegacy $unlock */
        foreach ($unlocks as $unlock) {
            // when a threshold is crossed, award a badge
            $count++;
            if ($count === $nextCountThreshold) {
                PlayerBadge::upsert(
                    [
                        [
                            'User' => $username,
                            'AwardType' => AwardType::AchievementUnlocksYield,
                            'AwardData' => $countLevel,
                            'AwardDate' => $unlock->Date,
                        ],
                    ],
                    ['User', 'AwardType', 'AwardData'],
                    ['AwardDate']
                );
                $countLevel++;

                $nextCountThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $countLevel);
            }

            $points += $unlock['Points'];
            if ($points >= $nextPointThreshold) {
                PlayerBadge::upsert(
                    [
                        [
                            'User' => $username,
                            'AwardType' => AwardType::AchievementPointsYield,
                            'AwardData' => $pointLevel,
                            'AwardDate' => $unlock->Date,
                        ],
                    ],
                    ['User', 'AwardType', 'AwardData'],
                    ['AwardDate']
                );
                $pointLevel++;

                $nextPointThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);
                if ($nextPointThreshold == 0) {
                    // if we run out of tiers, getBadgeThreshold returns 0, so everything will be >=. set to MAXINT
                    $nextPointThreshold = 0xFFFFFFFF;
                }
            }
        }

        // remove any extra badge tiers
        PlayerBadge::where('User', '=', $username)
            ->where('AwardType', '=', AwardType::AchievementUnlocksYield)
            ->where('AwardData', '>=', $countLevel)
            ->delete();

        PlayerBadge::where('User', '=', $username)
            ->where('AwardType', '=', AwardType::AchievementPointsYield)
            ->where('AwardData', '>=', $pointLevel)
            ->delete();

        // update the denormalized data
        User::where('User', '=', $username)
            ->update(['ContribCount' => $count, 'ContribYield' => $points]);
    }
}
