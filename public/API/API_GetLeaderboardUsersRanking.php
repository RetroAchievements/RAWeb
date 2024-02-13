<?php

/*
 *  API_GetLeaderboardUsersRanking
 *    lbID: Leaderboard ID
 *    lowerIsBetter: Boolean to indicate if a lower score is better
 *    offset: Offset for pagination
 *    limit: Number of records to return
 *
 *  Returns an array of objects with the following structure:
 *    - string User: Name of the user
 *    - int Score: User's score
 *    - datetime DateSubmitted: When the score was submitted
 *    - int Rank: Rank of the user in the specified leaderboard
 */

use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use Illuminate\Support\Facades\DB;

// Retrieve query parameters
$lbID = (int) request()->query('lbID');
$lowerIsBetter = (bool) request()->query('lowerIsBetter', false);
$offset = (int) request()->query('offset', 0);
$limit = (int) request()->query('limit', 100);

// Check if the leaderboard exists
$leaderboard = Leaderboard::find($lbID);
if (!$leaderboard) {
    // Return empty response if the leaderboard doesn't exist
    return response()->json(['LeaderboardID' => $lbID, 'Entries' => []]);
}

// Determine the order direction based on lowerIsBetter flag
$orderDirection = $lowerIsBetter ? 'asc' : 'desc';

// Retrieve leaderboard entries with calculated rank
$entries = LeaderboardEntry::where('leaderboard_id', $lbID)
    ->with('user:id,User')
    ->withRank($orderDirection)
    ->orderBy('score', $orderDirection)
    ->offset($offset)
    ->limit($limit)
    ->get();

// Transform entries for JSON response
$transformedEntries = $entries->map(static function ($entry) {
    return [
        'User' => $entry->user->User,
        'Score' => $entry->score,
        'DateSubmitted' => $entry->created_at->toISOString(),
        'Rank' => $entry->rank,
    ];
});

// Return the transformed entries
return response()->json(['LeaderboardID' => $lbID, 'Entries' => $transformedEntries]);
