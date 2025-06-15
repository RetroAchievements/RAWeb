<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Events\SiteBadgeAwarded;
use Illuminate\Support\Facades\DB;

class IncrementDeveloperContributionYieldAction
{
    /**
     * Incrementally update a developer's contribution yield when an achievement is unlocked or locked.
     * This is much more performant than recalculating all contributions from scratch.
     */
    public function execute(
        User $developer,
        Achievement $achievement,
        PlayerAchievement $playerAchievement,
        bool $isUnlock = true,
        bool $isHardcore = false
    ): void {
        // If we somehow made it here for an unofficial achievement, bail.
        if (!$achievement->is_published) {
            return;
        }

        // Don't count the developer's own unlocks.
        if ($playerAchievement->user_id === $developer->id) {
            return;
        }

        // If this is a hardcore unlock, check if it's just an upgrade of an existing softcore unlock.
        if ($isUnlock && $isHardcore) {
            // If the player already had a softcore unlock, this is an upgrade - don't count it again.
            // Bail.
            if (
                $playerAchievement->unlocked_at !== null
                && $playerAchievement->unlocked_at != $playerAchievement->unlocked_hardcore_at
            ) {
                return;
            }
        }

        $oldContribCount = $developer->ContribCount;
        $oldContribYield = $developer->ContribYield;

        if ($isUnlock) {
            DB::table('UserAccounts')
                ->where('ID', $developer->id)
                ->update([
                    'ContribCount' => DB::raw('ContribCount + 1'),
                    'ContribYield' => DB::raw('ContribYield + ' . $achievement->Points),
                ]);
        } else {
            DB::table('UserAccounts')
                ->where('ID', $developer->id)
                ->update([
                    'ContribCount' => DB::raw('CASE WHEN ContribCount > 0 THEN ContribCount - 1 ELSE 0 END'),
                    'ContribYield' => DB::raw('CASE WHEN ContribYield >= ' . $achievement->Points . ' THEN ContribYield - ' . $achievement->Points . ' ELSE 0 END'),
                ]);
        }

        $developer->refresh();

        // Only check for new badges if incrementing.
        if ($isUnlock) {
            $this->checkAndAwardNewBadges($developer, $oldContribYield, $oldContribCount);
        }
    }

    private function checkAndAwardNewBadges(User $developer, int $oldContribYield, int $oldContribCount): void
    {
        // Check for points yield badge.
        $this->checkAndAwardBadge($developer, AwardType::AchievementPointsYield, $oldContribYield, $developer->ContribYield);

        // Check for unlock count badge.
        $this->checkAndAwardBadge($developer, AwardType::AchievementUnlocksYield, $oldContribCount, $developer->ContribCount);
    }

    private function checkAndAwardBadge(User $developer, int $type, int $oldValue, int $currentValue): void
    {
        $tier = PlayerBadge::getNewBadgeTier($type, $oldValue, $currentValue);
        if ($tier === null) {
            return;
        }

        // Check if badge already awarded.
        $existingBadge = PlayerBadge::query()
            ->where('user_id', $developer->id)
            ->where('AwardType', '=', $type)
            ->where('AwardData', '=', $tier)
            ->exists();

        if ($existingBadge) {
            return;
        }

        // Get the display order from the highest existing badge.
        $lastBadge = PlayerBadge::query()
            ->where('user_id', $developer->id)
            ->where('AwardType', '=', $type)
            ->orderBy('AwardData', 'DESC')
            ->first();

        $displayOrder = $lastBadge ? $lastBadge->DisplayOrder : PlayerBadge::getNextDisplayOrder($developer);

        // Award the new badge.
        $badge = PlayerBadge::create([
            'user_id' => $developer->id,
            'AwardType' => $type,
            'AwardData' => $tier,
            'DisplayOrder' => $displayOrder,
        ]);

        SiteBadgeAwarded::dispatch($badge);
    }
}
