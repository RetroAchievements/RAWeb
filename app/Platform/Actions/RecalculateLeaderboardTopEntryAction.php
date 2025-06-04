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
            $leaderboards = Leaderboard::whereHas('topEntry', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();

            foreach ($leaderboards as $leaderboard) {
                $this->recalculateForLeaderboard($leaderboard);
            }

            return;
        }

        // If leaderboardId is provided, recalculate just that one.
        if ($leaderboardId !== null) {
            $leaderboard = Leaderboard::find($leaderboardId);
            $this->recalculateForLeaderboard($leaderboard);
        }
    }

    private function recalculateForLeaderboard(Leaderboard $leaderboard): void
    {
        $topEntry = $leaderboard->sortedEntries()->first();

        if ($topEntry) {
            $leaderboard->top_entry_id = $topEntry->id;
            $leaderboard->timestamps = false;
            $leaderboard->save();
        } else {
            // No valid entries found, clear the top entry.
            $leaderboard->top_entry_id = null;
            $leaderboard->timestamps = false;
            $leaderboard->save();
        }
    }
}
