<?php

/*
 *  API_GetLeaderboardSubmissions - returns info and submissions for a Leaderboard for the given ID
 *    i : leaderboardID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  string      Title                       name of the leaderboard
 *  int         Description                 details about what the leaderboard is tracking
 *  array       Entries
 *   object      [value]
 *    int        Count                      number of user entries returned in the response
 *    int        Total                      number of user entries the leaderboard actually has overall
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

$leaderboardId = request()->query('i');

$leaderboardData = Leaderboard::select("ID", "Title", "Description")
    ->where("ID", $leaderboardId)
    ->withCount("entries")
    ->first();

if (!$leaderboardData) {
    return response()->json([], 404);
}

$results = LeaderboardEntry::select("id", "user_id", "score", "updated_at")
    ->where("leaderboard_id", $leaderboardId)
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($entry) {
        return [
            'ID' => $entry->id,
            'UserID' => $entry->user_id,
            'Score' => $entry->score,
            'DateSubmitted' => $entry->updated_at->format('Y-m-d H:i:s'),
        ];
    });

$entries = new stdClass();
$entries->Count = count($results);
$entries->Total = $leaderboardData->entries_count;
$entries->Results = $results;

return response()->json([
    'Title' => $leaderboardData->Title,
    'Description' => $leaderboardData->Description,
    'Entries' => $entries,
]);
