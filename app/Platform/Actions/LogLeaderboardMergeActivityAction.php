<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Leaderboard;
use App\Models\User;

class LogLeaderboardMergeActivityAction
{
    /**
     * @param array{entries_transferred: int, entries_merged: int, entries_skipped: int} $stats
     */
    public function execute(
        Leaderboard $parentLeaderboard,
        Leaderboard $childLeaderboard,
        User $user,
        array $stats,
    ): void {
        $childLeaderboard->loadMissing('game');
        $parentLeaderboard->loadMissing('game');

        // Log on the parent leaderboard (the destination).
        activity()
            ->causedBy($user)
            ->performedOn($parentLeaderboard)
            ->withProperty('attributes', [
                'child_leaderboard_id' => $childLeaderboard->id,
                'child_leaderboard_title' => $childLeaderboard->title,
                'child_game_id' => $childLeaderboard->game_id,
                'child_game_title' => $childLeaderboard->game->title,
                'entries_transferred' => $stats['entries_transferred'],
                'entries_merged' => $stats['entries_merged'],
                'entries_skipped' => $stats['entries_skipped'],
            ])
            ->withProperty('child_leaderboard_id', $childLeaderboard->id)
            ->event('mergedFromLeaderboard')
            ->log('Merged from leaderboard');

        // Log on the child leaderboard (the source).
        activity()
            ->causedBy($user)
            ->performedOn($childLeaderboard)
            ->withProperty('attributes', [
                'parent_leaderboard_id' => $parentLeaderboard->id,
                'parent_leaderboard_title' => $parentLeaderboard->title,
                'parent_game_id' => $parentLeaderboard->game_id,
                'parent_game_title' => $parentLeaderboard->game->title,
                'entries_transferred' => $stats['entries_transferred'],
                'entries_merged' => $stats['entries_merged'],
                'entries_skipped' => $stats['entries_skipped'],
            ])
            ->withProperty('parent_leaderboard_id', $parentLeaderboard->id)
            ->event('mergedIntoLeaderboard')
            ->log('Merged into leaderboard');
    }
}
