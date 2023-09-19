<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateDeveloperContributionYield
{
    public function execute(User $user): void
    {
        // TODO refactor to player_achievements
        // TODO instead of iterating over all unlocks, aggregate the sums in the query

        $username = $user->username;

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
            ->where('Achievements.Flags', '=', AchievementFlag::OfficialCore)
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

                // TODO SiteBadgeAwarded::dispatch($badge);

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

                // TODO SiteBadgeAwarded::dispatch($badge);

                $pointLevel++;

                $nextPointThreshold = PlayerBadge::getBadgeThreshold(AwardType::AchievementPointsYield, $pointLevel);
                if ($nextPointThreshold == 0) {
                    // if we run out of tiers, getBadgeThreshold returns 0, so everything will be >=. set to MAXINT
                    $nextPointThreshold = PHP_INT_MAX;
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

    /**
     * @deprecated TODO still needed?
     */
    public function attributeDevelopmentAuthor(string $author, int $count, int $points): void
    {
        $user = User::firstWhere('User', $author);
        if ($user === null) {
            return;
        }

        $oldContribCount = $user->ContribCount;
        $oldContribYield = $user->ContribYield;

        // use raw statement to perform atomic update
        legacyDbStatement("UPDATE UserAccounts SET ContribCount = ContribCount + $count," .
            " ContribYield = ContribYield + $points WHERE User=:user", ['user' => $author]);

        $newContribTier = PlayerBadge::getNewBadgeTier(AwardType::AchievementUnlocksYield, $oldContribCount, $oldContribCount + $count);
        if ($newContribTier !== null) {
            $badge = AddSiteAward($author, AwardType::AchievementUnlocksYield, $newContribTier);
        }

        $newPointsTier = PlayerBadge::getNewBadgeTier(AwardType::AchievementPointsYield, $oldContribYield, $oldContribYield + $points);
        if ($newPointsTier !== null) {
            $badge = AddSiteAward($author, AwardType::AchievementPointsYield, $newPointsTier);
        }
    }

    /**
     * @deprecated TODO still needed?
     */
    public function recalculateDeveloperContribution(string $author): void
    {
        sanitize_sql_inputs($author);

        $query = "SELECT COUNT(*) AS ContribCount, SUM(Points) AS ContribYield
              FROM (SELECT aw.User, ach.ID, MAX(aw.HardcoreMode) as HardcoreMode, ach.Points
                    FROM Achievements ach LEFT JOIN Awarded aw ON aw.AchievementID=ach.ID
                    WHERE ach.Author='$author' AND aw.User != '$author'
                    AND ach.Flags=" . AchievementFlag::OfficialCore . "
                    GROUP BY 1,2) AS UniqueUnlocks";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $contribCount = 0;
            $contribYield = 0;

            if ($data = mysqli_fetch_assoc($dbResult)) {
                $contribCount = $data['ContribCount'] ?? 0;
                $contribYield = $data['ContribYield'] ?? 0;
            }

            $query = "UPDATE UserAccounts
                  SET ContribCount = $contribCount, ContribYield = $contribYield
                  WHERE User = '$author'";

            $dbResult = s_mysql_query($query);
            if (!$dbResult) {
                log_sql_fail();
            }
        }
    }
}
