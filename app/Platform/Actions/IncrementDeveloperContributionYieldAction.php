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
        bool $isHardcore = false,
    ): void {
        // If we somehow made it here for an unofficial achievement, bail.
        if (!$achievement->is_promoted) {
            return;
        }

        // Don't count the developer's own unlocks.
        if ($playerAchievement->user_id === $developer->id) {
            return;
        }

        // If this is a hardcore unlock, check if it's just an upgrade of an existing casual unlock.
        if ($isUnlock && $isHardcore) {
            // If the player already had a casual unlock, this is an upgrade so don't count it again.
            // Bail.
            if (
                $playerAchievement->unlocked_at !== null
                && $playerAchievement->unlocked_at != $playerAchievement->unlocked_hardcore_at
            ) {
                return;
            }
        }

        $developer->refresh();
        $oldContribCount = $developer->yield_unlocks;
        $oldContribYield = $developer->yield_points;

        if ($isUnlock) {
            DB::table('users')
                ->where('id', $developer->id)
                ->update([
                    'yield_unlocks' => DB::raw('yield_unlocks + 1'),
                    'yield_points' => DB::raw('yield_points + ' . $achievement->points),
                ]);
        } else {
            DB::table('users')
                ->where('id', $developer->id)
                ->update([
                    'yield_unlocks' => DB::raw('CASE WHEN yield_unlocks > 0 THEN yield_unlocks - 1 ELSE 0 END'),
                    'yield_points' => DB::raw('CASE WHEN yield_points >= ' . $achievement->points . ' THEN yield_points - ' . $achievement->points . ' ELSE 0 END'),
                ]);
        }

        // If credit goes to the author (not a maintainer), update the achievement's denormalized counter.
        // This counter is used by UpdateDeveloperContributionYieldAction for fast yield recalculation.
        // Only count unlocks from tracked (ranked) users to stay consistent with unlocks_total.
        //
        // This and the users update above are deliberately non-transactional.
        // A single PK update can block but cannot deadlock. A crash between the two
        // can leave author_yield_unlocks momentarily drifted from users.yield_*,
        // which self-heals on the next full recalc. This is an accepted tradeoff for
        // eliminating the deadlock cycle.
        if ($developer->id === $achievement->user_id) {
            $player = User::find($playerAchievement->user_id);
            if ($player && !$player->is_unranked) {
                // Raw update of only author_yield_unlocks - not increment()/decrement(),
                // which also bump updated_at and deadlock with the metrics recalc.
                DB::table('achievements')
                    ->where('id', $achievement->id)
                    ->update([
                        'author_yield_unlocks' => DB::raw(
                            $isUnlock
                                ? 'author_yield_unlocks + 1'
                                : 'CASE WHEN author_yield_unlocks > 0 THEN author_yield_unlocks - 1 ELSE 0 END'
                        ),
                    ]);
            }
        }

        $developer->refresh();

        if ($isUnlock) {
            $this->checkAndAwardNewBadges($developer, $oldContribYield, $oldContribCount);
        }
    }

    private function checkAndAwardNewBadges(User $developer, int $oldContribYield, int $oldContribCount): void
    {
        // Check for points yield badge.
        $this->checkAndAwardBadge($developer, AwardType::AchievementPointsYield, $oldContribYield, $developer->yield_points);

        // Check for unlock count badge.
        $this->checkAndAwardBadge($developer, AwardType::AchievementUnlocksYield, $oldContribCount, $developer->yield_unlocks);
    }

    private function checkAndAwardBadge(User $developer, AwardType $type, int $oldValue, int $currentValue): void
    {
        $tier = PlayerBadge::getNewBadgeTier($type, $oldValue, $currentValue);
        if ($tier === null) {
            return;
        }

        // Check if badge already awarded.
        $existingBadge = PlayerBadge::query()
            ->where('user_id', $developer->id)
            ->where('award_type', '=', $type)
            ->where('award_key', '=', $tier)
            ->exists();

        if ($existingBadge) {
            return;
        }

        // Get the display order from the highest existing badge.
        $lastBadge = PlayerBadge::query()
            ->where('user_id', $developer->id)
            ->where('award_type', '=', $type)
            ->orderBy('award_key', 'desc')
            ->first();

        $displayOrder = $lastBadge ? $lastBadge->order_column : PlayerBadge::getNextDisplayOrder($developer);

        // Award the new badge.
        $badge = PlayerBadge::create([
            'user_id' => $developer->id,
            'award_type' => $type,
            'award_key' => $tier,
            'order_column' => $displayOrder,
        ]);

        SiteBadgeAwarded::dispatch($badge);
    }
}
