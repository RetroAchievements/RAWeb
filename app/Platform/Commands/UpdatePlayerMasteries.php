<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePlayerMasteries extends Command
{
    protected $signature = 'ra:platform:update-player-masteries {username?}';

    protected $description = 'Recalculates masteries for a user';

    private array $gameAchievements = [];

    public function handle(): void
    {
        $username = $this->argument('username');
        if (!empty($username)) {
            $this->recalculate($username);

            return;
        }

        // TODO use PlayerBadge model
        $users = DB::table('SiteAwards')
            ->where('AwardType', '=', AwardType::Mastery)
            ->distinct()
            ->get(['User']);

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $this->recalculate($user->User);
            $progressBar->advance();
        }

        $progressBar->finish();
    }

    private function recalculate(string $username): void
    {
        // get all mastery awards for the user
        // TODO use PlayerBadge model
        $awards = DB::table('SiteAwards')
            ->where('AwardType', '=', AwardType::Mastery)
            ->where('User', '=', $username)
            ->get();

        $masteredGames = [];
        foreach ($awards as $award) {
            $masteredGames[$award->AwardData][$award->AwardDataExtra] = true;
        }

        foreach ($masteredGames as $gameID => $masteryData) {
            if (array_key_exists($gameID, $this->gameAchievements)) {
                $coreAchievementCount = $this->gameAchievements[$gameID];
            } else {
                // TODO use Achievement model
                $coreAchievementCount = DB::table('Achievements')
                    ->where('GameID', '=', $gameID)
                    ->where('Flags', '=', AchievementFlag::OfficialCore)
                    ->count();
                $this->gameAchievements[$gameID] = $coreAchievementCount;
            }

            // TODO use PlayerAchievement model
            $userUnlocks = DB::table('Awarded')
                ->select(['Awarded.HardcoreMode', DB::raw('COUNT(Awarded.AchievementID) AS Num')])
                ->leftJoin('Achievements', 'Achievements.ID', '=', 'Awarded.AchievementID')
                ->where('Achievements.GameID', '=', $gameID)
                ->where('Awarded.User', '=', $username)
                ->where('Achievements.Flags', '=', AchievementFlag::OfficialCore)
                ->groupBy(['Awarded.HardcoreMode'])
                ->pluck('Num', 'Awarded.HardcoreMode')
                ->toArray();

            $hardcoreCount = $userUnlocks[UnlockMode::Hardcore] ?? 0;
            $softcoreCount = $userUnlocks[UnlockMode::Softcore] ?? 0;

            $deleteAward = false;
            $demoteAward = false;
            if ($hardcoreCount === 0 && $softcoreCount === 0) {
                // user has no achievements for the set. if the set is empty, assume it
                // was demoted and keep the badge, otherwise assume they did a full reset
                // and destroy the badge.
                $deleteAward = ($coreAchievementCount !== 0);
            } elseif ($hardcoreCount < $coreAchievementCount) {
                if ($softcoreCount < $coreAchievementCount) {
                    // if the user has at least one unlock for the set, assume there was
                    // a revision and do nothing. if they want to get rid of the badge,
                    // they can reset one or more of the achievements they have.
                } elseif ($masteryData[UnlockMode::Hardcore] ?? false) {
                    // user has a hardcore badge, but only the softcore achievements, demote it
                    $demoteAward = true;
                }
            }

            if ($deleteAward) {
                // user no longer has all achievements for the set, revoke their badge
                // TODO use PlayerBadge model
                DB::table('SiteAwards')
                    ->where('AwardType', '=', AwardType::Mastery)
                    ->where('User', '=', $username)
                    ->where('AwardData', '=', $gameID)
                    ->delete();
            } elseif ($demoteAward) {
                // user has all softcore achievements for the set, but no longer has
                // all hardcore achievements for the set
                if ($masteryData[UnlockMode::Softcore] ?? false) {
                    // user already has a separate softcore badge, delete the hardcore one
                    // TODO use PlayerBadge model
                    DB::table('SiteAwards')
                        ->where('AwardType', '=', AwardType::Mastery)
                        ->where('User', '=', $username)
                        ->where('AwardData', '=', $gameID)
                        ->where('AwardDataExtra', '=', UnlockMode::Hardcore)
                        ->delete();
                } else {
                    // user only has a hardcore badge, demote it to softcore
                    DB::connection('mysql')
                        ->table('SiteAwards')
                        ->where('AwardType', '=', AwardType::Mastery)
                        ->where('User', '=', $username)
                        ->where('AwardData', '=', $gameID)
                        ->update(['AwardDataExtra' => UnlockMode::Softcore]);
                }
            }
        }
    }
}
