<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Events\SiteBadgeAwarded;
use Illuminate\Support\Facades\DB;

class UpdateDeveloperContributionYieldAction
{
    public function execute(User $user): void
    {
        // Calculate total contribution by combining authored and maintained achievements.
        $contributionStats = $this->calculateContributions($user);

        $newContribYield = $contributionStats['Points'];
        $newContribCount = $contributionStats['Count'];

        // Update badges and user record.
        $this->ensureBadge($user, AwardType::AchievementPointsYield, $newContribYield);
        $this->ensureBadge($user, AwardType::AchievementUnlocksYield, $newContribCount);

        $user->ContribYield = $newContribYield;
        $user->ContribCount = $newContribCount;
        $user->save();
    }

    private function calculateContributions(User $user): array
    {
        // Calculate author contributions (achievements created by the user where credit wasn't given to a maintainer).
        $authorSql = <<<SQL
            SELECT 
                SUM(a.Points) as author_points,
                COUNT(*) as author_count
            FROM Achievements a
            JOIN player_achievements pa ON pa.achievement_id = a.ID
            LEFT JOIN achievement_maintainer_unlocks amu ON amu.player_achievement_id = pa.id
            WHERE a.user_id = :user_id
              AND a.Flags = :flags
              AND pa.user_id != :user_id2
              AND amu.id IS NULL
        SQL;
        $authorResults = DB::select($authorSql, [
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'flags' => AchievementFlag::OfficialCore->value,
        ]);

        // Calculate maintainer contributions (unlocks credited to the user as a maintainer).
        $maintainerSql = <<<SQL
            SELECT 
                SUM(a.Points) as maintainer_points,
                COUNT(*) as maintainer_count
            FROM achievement_maintainer_unlocks amu
            JOIN Achievements a ON a.ID = amu.achievement_id
            WHERE amu.maintainer_id = :user_id
        SQL;
        $maintainerResults = DB::select($maintainerSql, [
            'user_id' => $user->id,
        ]);

        // Calculate totals.
        $authorPoints = (int) ($authorResults[0]->author_points ?? 0);
        $authorCount = (int) ($authorResults[0]->author_count ?? 0);
        $maintainerPoints = (int) ($maintainerResults[0]->maintainer_points ?? 0);
        $maintainerCount = (int) ($maintainerResults[0]->maintainer_count ?? 0);

        return [
            'Points' => $authorPoints + $maintainerPoints,
            'Count' => $authorCount + $maintainerCount,
        ];
    }

    private function getChronologicalUnlocks(User $user, int $type, int $offset = 0, int $limit = 10000): array
    {
        $pointsField = ($type == AwardType::AchievementPointsYield) ? 'a.Points' : '1';

        // Get the unlocks in chronological order.
        $unlocksSql = <<<SQL
            -- Author unlocks (where no maintainer has claimed credit).
            SELECT 
                {$pointsField} as Points,
                COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) as unlock_date
            FROM Achievements a
            JOIN player_achievements pa ON pa.achievement_id = a.ID
            LEFT JOIN achievement_maintainer_unlocks amu ON amu.player_achievement_id = pa.id
            WHERE a.user_id = :user_id
            AND a.Flags = :flags
            AND pa.user_id != :user_id2
            AND amu.id IS NULL
            UNION ALL
            
            -- Maintainer unlocks.
            SELECT 
                {$pointsField} as Points,
                COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) as unlock_date
            FROM achievement_maintainer_unlocks amu
            JOIN player_achievements pa ON pa.id = amu.player_achievement_id
            JOIN Achievements a ON a.ID = amu.achievement_id
            WHERE amu.maintainer_id = :user_id3
            ORDER BY unlock_date
            LIMIT :limit OFFSET :offset
        SQL;

        $unlocks = DB::select($unlocksSql, [
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'user_id3' => $user->id,
            'flags' => AchievementFlag::OfficialCore->value,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $unlocks;
    }

    private function ensureBadge(User $user, int $type, int $newContrib): void
    {
        $tier = PlayerBadge::getNewBadgeTier($type, 0, $newContrib);
        if ($tier === null) {
            // no badge earned, or no more badges to earn
            return;
        }

        $badge = PlayerBadge::query()
            ->where('user_id', $user->id)
            ->where('AwardType', '=', $type)
            ->orderBy('AwardData', 'DESC')
            ->first();

        if ($badge && $badge->AwardData >= $tier) {
            // badge already awarded
            return;
        }

        $displayOrder = $badge ? $badge->DisplayOrder : PlayerBadge::getNextDisplayOrder($user);

        // if there's a gap between tiers, backfill the missing awards
        $oldTier = $badge ? $badge->AwardData : -1;
        if ($tier - $oldTier > 1) {
            $this->backfillMissingBadges($user, $type, $oldTier, $tier, $displayOrder);
        }

        // add new award
        $badge = PlayerBadge::create([
            'user_id' => $user->id,
            'AwardType' => $type,
            'AwardData' => $tier,
            'DisplayOrder' => $displayOrder,
        ]);

        SiteBadgeAwarded::dispatch($badge);
    }

    private function backfillMissingBadges(User $user, int $type, int $lastAwardedTier, int $newTier, int $displayOrder): void
    {
        $total = 0;
        $tier = 0;
        $nextThreshold = PlayerBadge::getBadgeThreshold($type, $tier);
        $offset = 0;
        $chunkSize = 10000;

        while (true) {
            $unlocks = $this->getChronologicalUnlocks($user, $type, $offset, $chunkSize);
            if (empty($unlocks)) {
                break;
            }

            foreach ($unlocks as $unlock) {
                if ($type == AwardType::AchievementPointsYield) {
                    $total += $unlock->Points;
                } else {
                    $total++;
                }

                while ($total >= $nextThreshold) {
                    if ($tier > $lastAwardedTier) {
                        PlayerBadge::create([
                            'user_id' => $user->id,
                            'AwardType' => $type,
                            'AwardData' => $tier,
                            'AwardDate' => $unlock->unlock_date,
                            'DisplayOrder' => $displayOrder,
                        ]);
                    }

                    $tier++;
                    if ($tier == $newTier) {
                        return;
                    }

                    $nextThreshold = PlayerBadge::getBadgeThreshold($type, $tier);
                    if ($nextThreshold < $total) {
                        // unexpected. bail
                        return;
                    }
                }
            }

            $offset += $chunkSize;
        }
    }
}
