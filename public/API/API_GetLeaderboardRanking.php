<?php

/*
 *  API_GetLeaderboardRanking
 *    u : username
 *    lbID : leaderboard id
 *    lowerIsBetter : boolean to indicate if a lower score is better
 *
 *  Returns:
 *  int        Rank                    rank of the user in the specified leaderboard
 */

use App\Models\User;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;

// Find the user
$user = User::firstWhere('User', request()->query('u'));
if (!$user) {
    return response()->json([]);
}

$lbID = (int) request()->query('lbID');
$lowerIsBetter = (bool) request()->query('lowerIsBetter');

// Check if user and leaderboard exist
if (!$user || !Leaderboard::find($lbID)) {
    return response()->json([
        'User' => $userName,
        'LeaderboardID' => $lbID,
        'Rank' => null,
    ]);
}

// Retrieve leaderboard entries and calculate rank
$entries = LeaderboardEntry::where('leaderboard_id', $lbID)
    ->orderBy('score', $lowerIsBetter ? 'asc' : 'desc')
    ->orderBy('created_at', 'asc') // assuming created_at is the submission time
    ->get();

$rank = 1;
foreach ($entries as $entry) {
    if ($entry->user_id === $user->id) {
        break;
    }
    $rank++;
}

return response()->json([
    'User' => $userName,
    'LeaderboardID' => $lbID,
    'Rank' => $rank,
]);
