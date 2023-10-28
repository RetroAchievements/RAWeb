<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Events\SiteBadgeAwarded;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateDeveloperContributionYield
{
    public function execute(User $user): void
    {
        $contribAchievements = PlayerAchievement::where('player_achievements.user_id', '!=', $user->id)
            ->join('Achievements', 'Achievements.ID', '=', 'achievement_id')
            ->where('Achievements.Flags', '=', AchievementFlag::OfficialCore)
            ->where('Achievements.Author', '=', $user->User)
            ->select([DB::raw('SUM(Achievements.Points) AS Points'), DB::raw('COUNT(*) as Count')])
            ->first();

        $newContribYield = (int) $contribAchievements['Points'];
        $this->ensureBadge($user, AwardType::AchievementPointsYield, $newContribYield);

        $newContribCount = (int) $contribAchievements['Count'];
        $this->ensureBadge($user, AwardType::AchievementUnlocksYield, $newContribCount);

        $user->ContribYield = $newContribYield;
        $user->ContribCount = $newContribCount;
        $user->save();
    }

    private function ensureBadge(User $user, int $type, int $newContrib): void
    {
        $tier = PlayerBadge::getNewBadgeTier($type, 0, $newContrib);
        if ($tier === null) {
            // no badge earned, or no more badges to earn
            return;
        }

        $badge = PlayerBadge::where('User', $user->User)
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
            'User' => $user->User,
            'AwardType' => $type,
            'AwardData' => $tier,
            'DisplayOrder' => $displayOrder,
        ]);

        SiteBadgeAwarded::dispatch($badge);
    }

    private function backfillMissingBadges(User $user, int $type, int $lastAwardedTier, int $newTier, int $displayOrder): void
    {
        $unlocks = PlayerAchievement::where('player_achievements.user_id', '!=', $user->id)
            ->join('Achievements', 'Achievements.ID', '=', 'achievement_id')
            ->where('Achievements.Flags', '=', AchievementFlag::OfficialCore)
            ->where('Achievements.Author', '=', $user->User)
            ->orderBy('unlocked_at');

        if ($type == AwardType::AchievementPointsYield) {
            $unlocks = $unlocks->select('unlocked_at', 'Points');
        } else {
            $unlocks = $unlocks->select('unlocked_at');
        }

        $total = 0;
        $tier = 0;
        $nextThreshold = PlayerBadge::getBadgeThreshold($type, $tier);

        foreach ($unlocks->get() as $unlock) {
            if ($type == AwardType::AchievementPointsYield) {
                $total += $unlock->Points;
            } else {
                $total++;
            }

            while ($total >= $nextThreshold) {
                if ($tier > $lastAwardedTier) {
                    PlayerBadge::create([
                        'User' => $user->User,
                        'AwardType' => $type,
                        'AwardData' => $tier,
                        'AwardDate' => $unlock->unlocked_at,
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
    }
}
