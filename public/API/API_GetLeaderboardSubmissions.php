<?php

/*
 *  API_GetLeaderboard - returns a info and submissions for a Leaderboard for the given ID
 *    i : leaderboardID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of user entries returned in the response
 *  int         Total                       number of user entries the leaderboard actually has overall
 *  string      Title                       name of the leaderboard
 *  int         Description                 details about what the leaderboard is tracking
 *  array       Entries
 *   object      [value]
 *    int        Rank                       user's leaderboard rank
 *    string     User                       name of user
 *    string     Score                      string value of the proper ValueFormat of the leaderboard entry //this feels wrong
 *    string     DateSubmitted              an ISO8601 timestamp string for when the entry was submitted
 */

use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'min:1', new CtypeAlnum()],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$leaderboard = Leaderboard::firstWhere('ID', request()->query('i'));
if (!$leaderboard) {
    return response()->json([], 404);
}

$totalLeaderboardEntries = LeaderboardEntry::where('leaderboard_id', $leaderboard->ID)
    ->count();

$entries = LeaderboardEntry::where('leaderboard_id', $leaderboard->ID)
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($entry) {
        return [
            'ID' => $entry->id,
            'UserID' => $entry->user_id,
            'Score' => $entry->score,
            'DateSubmitted' => $entry->updated_at->toDateTimeString(),
        ];
    });

return response()->json([
    'Count' => count($entries),
    'Total' => $totalLeaderboardEntries,
    'Entries' => $entries,
]);
