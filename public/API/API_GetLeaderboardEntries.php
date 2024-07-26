<?php

/*
 *  API_GetLeaderboardEntries - returns entries for a Leaderboard for the given ID
 *    i : leaderboardID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of user entries returned in the response
 *  int         Total                       number of user entries the leaderboard actually has overall
 *  array       Results
 *   object      [value]
 *    int        Rank                       user's leaderboard rank
 *    string     User                       name of user
 *    string     Score                      raw string value of the leaderboard entry's score
 *    string     FormattedScore             string value of the formatted leaderboard entry's score (reference GetGameLeaderboard for Format type)
 *    string     DateSubmitted              an ISO8601 timestamp string for when the entry was submitted
 */

use App\Models\Leaderboard;
use App\Platform\Enums\ValueFormat;
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

$leaderboard = Leaderboard::firstWhere("ID", $leaderboardId);

if (!$leaderboard) {
    return response()->json([], 404);
}

$fetchedLeaderboardData = GetLeaderboardData($leaderboard, null, $count, $offset);

$results = [];
foreach ($fetchedLeaderboardData['Entries'] as $entry) {
    $results[] = [
        'User' => $entry['User'],
        'DateSubmitted' => date('c', $entry['DateSubmitted']),
        'Score' => $entry['Score'],
        'FormattedScore' => ValueFormat::format($entry['Score'], $leaderboard->Format),
        'Rank' => $entry['Rank'],
    ];
}

return response()->json([
    'Count' => count($fetchedLeaderboardData['Entries']),
    'Total' => $fetchedLeaderboardData['TotalEntries'],
    'Results' => $results,
]);
