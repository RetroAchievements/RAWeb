<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Leaderboard;

class GetRankedLeaderboardEntriesAction
{
    public function execute(Leaderboard $leaderboard, int $offset, int $count): array
    {
        $result = [];

        $index = $offset + 1;
        $rankScore = null;
        $rank = -1;

        $entries = $leaderboard->sortedEntries()->with('user')->skip($offset)->take($count);
        foreach ($entries->get() as $entry) {
            if ($entry->score !== $rankScore) {
                if ($rankScore === null) {
                    // first entry, get its rank
                    $rank = $leaderboard->getRank($entry->score);
                } else {
                    // non-first entry, just update rank to be the current index
                    $rank = $index;
                }
                $rankScore = $entry->score;
            }

            $entry->rank = $rank;
            $result[] = $entry;

            $index++;
        }

        return $result;
    }
}
