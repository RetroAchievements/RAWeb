<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Leaderboard;
use App\Models\User;

class RecalculateLeaderboardTopEntryAction
{
    /**
     * Recalculate the top entry for a specific leaderboard or
     * recalculate all leaderboards for a specific user.
     *
     * @param int|null $leaderboardId if provided, only recalculate the top entry for this leaderboard
     * @param User|null $user if provided, recalculate all leaderboards where this user is the top entry
     */
    public function execute(?int $leaderboardId = null, ?User $user = null): void
    {
        // If user is provided, find all leaderboards where they have the top entry.
        if ($user !== null) {
            $leaderboards = Leaderboard::where('top_user_id', $user->id)->get();

            foreach ($leaderboards as $leaderboard) {
                $this->recalculateForLeaderboard($leaderboard->id);
            }

            return;
        }

        // If leaderboardId is provided, recalculate just that one.
        if ($leaderboardId !== null) {
            $this->recalculateForLeaderboard($leaderboardId);
        }
    }

    private function recalculateForLeaderboard(int $leaderboardId): void
    {
        $leaderboard = Leaderboard::find($leaderboardId);
        if (!$leaderboard) {
            return;
        }

        $topEntry = $leaderboard->sortedEntries()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('Untracked', 0)
                      ->whereNull('unranked_at')
                      ->whereNull('banned_at');
            })
            ->first();

        if ($topEntry) {
            $leaderboard->top_entry_id = $topEntry->id;
            $leaderboard->top_user_id = $topEntry->user_id;
            $leaderboard->top_score = $topEntry->score;
            $leaderboard->top_entry_updated_at = $topEntry->updated_at;
            $leaderboard->save();
        } else {
            // No valid entries found, clear the top entry.
            $leaderboard->top_entry_id = null;
            $leaderboard->top_user_id = null;
            $leaderboard->top_score = null;
            $leaderboard->top_entry_updated_at = null;
            $leaderboard->save();
        }
    }
}
