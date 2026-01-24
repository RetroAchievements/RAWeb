<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MergeLeaderboardsAction
{
    /**
     * Merges all entries from a child leaderboard into a parent leaderboard.
     * Duplicate user entries are resolved by keeping the better score.
     * The child leaderboard is set to Unpublished after the merge.
     *
     * @return array{entries_transferred: int, entries_merged: int, entries_skipped: int}
     */
    public function execute(
        Leaderboard $parentLeaderboard,
        Leaderboard $childLeaderboard,
        User $user,
    ): array {
        $this->validate($parentLeaderboard, $childLeaderboard);

        return DB::transaction(function () use ($parentLeaderboard, $childLeaderboard, $user) {
            $stats = [
                'entries_transferred' => 0,
                'entries_merged' => 0,
                'entries_skipped' => 0,
            ];

            $childEntries = LeaderboardEntry::where('leaderboard_id', $childLeaderboard->id)->get();

            $childUserIds = $childEntries->pluck('user_id')->unique();
            $parentEntries = LeaderboardEntry::where('leaderboard_id', $parentLeaderboard->id)
                ->whereIn('user_id', $childUserIds)
                ->get()
                ->keyBy('user_id');

            // Collect operations to execute in bulk, otherwise this takes forever.
            $transferIds = [];
            $mergeUpdates = [];
            $deleteIds = [];

            foreach ($childEntries as $childEntry) {
                $existingParentEntry = $parentEntries->get($childEntry->user_id);

                if (!$existingParentEntry) {
                    // No conflict - transfer the entry to the parent leaderboard.
                    $transferIds[] = $childEntry->id;
                    $stats['entries_transferred']++;
                } else {
                    // The user has an entry in both leaderboards - keep whichever one is better.
                    $childIsBetter = $parentLeaderboard->isBetterScore($childEntry->score, $existingParentEntry->score);
                    $scoresAreEqual = $childEntry->score === $existingParentEntry->score;
                    $childIsEarlier = $childEntry->created_at < $existingParentEntry->created_at;

                    if ($childIsBetter || ($scoresAreEqual && $childIsEarlier)) {
                        // The child entry wins. Queue an update for the parent entry.
                        $mergeUpdates[] = [
                            'parent_id' => $existingParentEntry->id,
                            'score' => $childEntry->score,
                            'trigger_id' => $childEntry->trigger_id,
                            'player_session_id' => $childEntry->player_session_id,
                            'created_at' => $childEntry->created_at,
                            'updated_at' => $childEntry->updated_at,
                        ];
                        $stats['entries_merged']++;
                    } else {
                        $stats['entries_skipped']++;
                    }

                    // Soft delete the child entry regardless of which score won.
                    $deleteIds[] = $childEntry->id;
                }
            }

            // Execute a bulk transfer - move entries to parent leaderboard.
            if (!empty($transferIds)) {
                LeaderboardEntry::whereIn('id', $transferIds)
                    ->update(['leaderboard_id' => $parentLeaderboard->id]);
            }

            // Execute bulk merges - update parent entries with child data.
            if (!empty($mergeUpdates)) {
                $updates = collect($mergeUpdates)->map(fn ($update) => [
                    'id' => $update['parent_id'],
                    'leaderboard_id' => $parentLeaderboard->id,
                    'user_id' => 0, // placeholder, not updated
                    'score' => $update['score'],
                    'trigger_id' => $update['trigger_id'],
                    'player_session_id' => $update['player_session_id'],
                    'created_at' => $update['created_at'],
                    'updated_at' => $update['updated_at'],
                ])->all();

                LeaderboardEntry::upsert(
                    $updates,
                    ['id'],
                    ['score', 'trigger_id', 'player_session_id', 'created_at', 'updated_at']
                );
            }

            // Execute bulk soft delete for child entries that had conflicts.
            if (!empty($deleteIds)) {
                LeaderboardEntry::whereIn('id', $deleteIds)->delete();
            }

            // Set the child leaderboard to Unpublished and clear its top entry.
            $childLeaderboard->state = LeaderboardState::Unpublished;
            $childLeaderboard->top_entry_id = null;
            $childLeaderboard->save();

            // Recalculate the parent leaderboard's top entry.
            (new RecalculateLeaderboardTopEntryAction())->execute($parentLeaderboard->id);

            // Log the merge activity.
            (new LogLeaderboardMergeActivityAction())->execute(
                $parentLeaderboard,
                $childLeaderboard,
                $user,
                $stats
            );

            return $stats;
        });
    }

    private function validate(Leaderboard $parentLeaderboard, Leaderboard $childLeaderboard): void
    {
        if ($parentLeaderboard->id === $childLeaderboard->id) {
            throw new InvalidArgumentException('Cannot merge a leaderboard with itself.');
        }

        if ($parentLeaderboard->format !== $childLeaderboard->format) {
            throw new InvalidArgumentException(
                "Leaderboard formats do not match. Parent: {$parentLeaderboard->format}, Child: {$childLeaderboard->format}"
            );
        }

        if ($parentLeaderboard->rank_asc !== $childLeaderboard->rank_asc) {
            throw new InvalidArgumentException(
                "Leaderboard rank directions do not match. "
                . "Parent: {$this->formatRankDirection($parentLeaderboard->rank_asc)}, "
                . "Child: {$this->formatRankDirection($childLeaderboard->rank_asc)}"
            );
        }
    }

    private function formatRankDirection(bool $rankAsc): string
    {
        return $rankAsc ? 'Lower is better' : 'Higher is better';
    }
}
