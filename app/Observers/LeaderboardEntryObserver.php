<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LeaderboardEntry;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;

class LeaderboardEntryObserver
{
    public function created(LeaderboardEntry $leaderboardEntry): void
    {
        $this->updateLeaderboardTopEntry($leaderboardEntry);
    }

    public function updated(LeaderboardEntry $leaderboardEntry): void
    {
        $this->updateLeaderboardTopEntry($leaderboardEntry);
    }

    public function deleted(LeaderboardEntry $leaderboardEntry): void
    {
        (new RecalculateLeaderboardTopEntryAction())->execute($leaderboardEntry->leaderboard_id);
    }

    public function restored(LeaderboardEntry $leaderboardEntry): void
    {
        $this->updateLeaderboardTopEntry($leaderboardEntry);
    }

    public function forceDeleted(LeaderboardEntry $leaderboardEntry): void
    {
        (new RecalculateLeaderboardTopEntryAction())->execute($leaderboardEntry->leaderboard_id);
    }

    public function updateLeaderboardTopEntry(LeaderboardEntry $leaderboardEntry): void
    {
        $leaderboard = $leaderboardEntry->leaderboard;
        if (!$leaderboard) {
            return;
        }

        // Skip if the user is unranked, untracked, or banned.
        $user = $leaderboardEntry->user;
        if (!$user || $user->unranked_at || $user->Untracked || $user->banned_at) {
            return;
        }

        // Check if this entry is the top entry for the leaderboard.
        if ($leaderboard->top_entry_id) {
            $currentTopEntry = $leaderboard->topEntry;

            // If the current top entry was deleted or no longer valid, recalculate.
            if (
                !$currentTopEntry
                || $currentTopEntry->deleted_at
                || !$currentTopEntry->user
                || $currentTopEntry->user->Untracked
                || $currentTopEntry->user->unranked_at
                || $currentTopEntry->user->banned_at
            ) {
                (new RecalculateLeaderboardTopEntryAction())->execute($leaderboard->id);

                return;
            }

            // Check if the new entry is better than the current top entry.
            $isNewEntryBetter = $leaderboard->isBetterScore($leaderboardEntry->score, $currentTopEntry->score);

            // If scores are tied, the earlier entry wins.
            if ($leaderboardEntry->score === $currentTopEntry->score && $leaderboardEntry->updated_at < $currentTopEntry->updated_at) {
                $isNewEntryBetter = true;
            }

            if ($isNewEntryBetter) {
                // This is a new top entry - update the leaderboard.
                $leaderboard->top_entry_id = $leaderboardEntry->id;
                $leaderboard->save();
            }
        } else {
            // No top entry set yet, so do a recalculation.
            (new RecalculateLeaderboardTopEntryAction())->execute($leaderboard->id);
        }
    }
}
