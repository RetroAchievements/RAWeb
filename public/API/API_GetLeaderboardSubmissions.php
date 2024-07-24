<?php

/*
 *  API_GetLeaderboardSubmissions - returns submissions for a Leaderboard for the given ID
 *    i : leaderboardID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of user entries returned in the response
 *  int         Total                       number of user entries the leaderboard actually has overall
 *  array       Results
 *   object      [value]
 *    int        Rank                       user's leaderboard rank
 *    string     User                       name of user
 *    string     Score                      string value of the proper ValueFormat of the leaderboard entry //this feels wrong
 *    string     DateSubmitted              an ISO8601 timestamp string for when the entry was submitted
 */

use App\Models\Leaderboard;
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

$leaderboardData = Leaderboard::where("ID", $leaderboardId)
    ->withCount("entries")
    ->first();

if (!$leaderboardData) {
    return response()->json([], 404);
}

$fetchedLeaderboardData = GetLeaderboardData($leaderboardData, null, $count, $offset);

$results = [];
foreach ($fetchedLeaderboardData['Entries'] as $entry) {
    $results[] = [
        'User' => $entry['User'],
        'DateSubmitted' => date('Y-m-d H:i:s', $entry['DateSubmitted']),
        'Score' => $entry['Score'],
        'Rank' => $entry['Rank'],
        ];
}

return response()->json([
    'Count' => count($fetchedLeaderboardData['Entries']),
    'Total' => $fetchedLeaderboardData['TotalEntries'],
    'Results' => usort($results, function ($a, $b) {
        return $a['Rank'] - $b['Rank'];
    }),
]);
