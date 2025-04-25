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
        $sql = <<<SQL
            WITH authored_achievements AS (
                SELECT ID, Points 
                FROM Achievements
                WHERE user_id = :user_id AND Flags = :flags
            ),
            maintained_achievements AS (
                SELECT 
                    a.ID,
                    a.Points,
                    m.effective_from,
                    m.effective_until
                FROM achievement_maintainers m
                JOIN Achievements a ON a.ID = m.achievement_id
                WHERE m.user_id = :user_id2
            )
            SELECT SUM(Points) as total_points, SUM(unlock_count) as total_unlocks
            FROM (
                -- Author unlocks.
                SELECT 
                    SUM(a.Points) as Points,
                    COUNT(*) as unlock_count
                FROM authored_achievements a
                JOIN player_achievements pa ON pa.achievement_id = a.ID
                LEFT JOIN achievement_maintainers m ON m.achievement_id = a.ID
                WHERE pa.user_id != :user_id3
                    AND m.id IS NULL
    
                UNION ALL
    
                -- Maintainer unlocks during their maintainership period.
                SELECT 
                    SUM(ma.Points) as Points,
                    COUNT(*) as unlock_count  
                FROM maintained_achievements ma
                JOIN player_achievements pa ON pa.achievement_id = ma.ID
                WHERE pa.user_id != :user_id4
                    AND COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) >= ma.effective_from
                    AND (ma.effective_until IS NULL OR COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) < ma.effective_until)
            ) contributions
        SQL;

        $results = DB::select($sql, [
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'user_id3' => $user->id,
            'user_id4' => $user->id,
            'flags' => AchievementFlag::OfficialCore->value,
        ]);

        return [
            'Points' => (int) ($results[0]->total_points ?? 0),
            'Count' => (int) ($results[0]->total_unlocks ?? 0),
        ];
    }

    private function getChronologicalUnlocks(User $user, int $type, int $offset = 0, int $limit = 10000): array
    {
        $pointsField = ($type == AwardType::AchievementPointsYield) ? 'a.Points' : '1';
        $maintainerPointsField = ($type == AwardType::AchievementPointsYield) ? 'ma.Points' : '1';

        $sql = <<<SQL
            WITH authored_achievements AS (
                SELECT ID, Points 
                FROM Achievements
                WHERE user_id = :user_id AND Flags = :flags
            ),
            maintained_achievements AS (
                SELECT 
                    a.ID,
                    a.Points,
                    m.effective_from,
                    m.effective_until
                FROM achievement_maintainers m
                JOIN Achievements a ON a.ID = m.achievement_id
                WHERE m.user_id = :user_id2
            )
            SELECT Points, unlock_date 
            FROM (
                -- Author unlocks.
                SELECT 
                    {$pointsField} as Points,
                    COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) as unlock_date
                FROM authored_achievements a
                JOIN player_achievements pa ON pa.achievement_id = a.ID
                LEFT JOIN achievement_maintainers m ON m.achievement_id = a.ID
                WHERE pa.user_id != :user_id3
                    AND m.id IS NULL
    
                UNION ALL
    
                -- Maintainer unlocks during their maintainership period.
                SELECT 
                    {$maintainerPointsField} as Points,
                    COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) as unlock_date
                FROM maintained_achievements ma
                JOIN player_achievements pa ON pa.achievement_id = ma.ID
                WHERE pa.user_id != :user_id4
                    AND COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) >= ma.effective_from
                    AND (ma.effective_until IS NULL OR COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) < ma.effective_until)
            ) all_unlocks
            ORDER BY unlock_date
            LIMIT :limit OFFSET :offset
        SQL;

        return DB::select($sql, [
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'user_id3' => $user->id,
            'user_id4' => $user->id,
            'flags' => AchievementFlag::OfficialCore->value,
            'limit' => $limit,
            'offset' => $offset,
        ]);
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
