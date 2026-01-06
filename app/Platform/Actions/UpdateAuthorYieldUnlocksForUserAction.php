<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementMaintainerUnlock;
use App\Models\PlayerAchievement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAuthorYieldUnlocksForUserAction
{
    /**
     * Update author_yield_unlocks for all achievements unlocked by this user
     * when their ranked status changes.
     */
    public function execute(User $user): void
    {
        $allUserPlayerAchievementIds = PlayerAchievement::where('user_id', $user->id)
            ->pluck('id');
        if ($allUserPlayerAchievementIds->isEmpty()) {
            return;
        }

        // Get player_achievement IDs that were credited to maintainers (not authors).
        // These don't affect author_yield_unlocks.
        $maintainerCreditedIds = AchievementMaintainerUnlock::whereIn('player_achievement_id', $allUserPlayerAchievementIds)
            ->pluck('player_achievement_id');

        // Get achievement IDs where the unlock was credited to the author.
        // Exclude: achievements authored by this user, and maintainer-credited unlocks.
        $authorCreditedAchievementIds = PlayerAchievement::where('user_id', $user->id)
            ->whereNotIn('id', $maintainerCreditedIds)
            ->whereHas('achievement', fn ($q) => $q->where('user_id', '!=', $user->id))
            ->pluck('achievement_id');
        if ($authorCreditedAchievementIds->isEmpty()) {
            return;
        }

        // Bulk update: decrement if unranked, increment if reranked.
        Achievement::whereIn('id', $authorCreditedAchievementIds)
            ->update([
                'author_yield_unlocks' => DB::raw(
                    $user->is_unranked
                        ? 'CASE WHEN author_yield_unlocks > 0 THEN author_yield_unlocks - 1 ELSE 0 END'
                        : 'author_yield_unlocks + 1'
                ),
            ]);
    }
}
