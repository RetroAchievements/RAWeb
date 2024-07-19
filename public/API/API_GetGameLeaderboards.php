<?php

/*
 *  API_GetGameLeaderboards - returns a list of Leaderboards for the give GameID
 *    i : gameID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of want to play game records returned in the response
 *  int         Total                       number of want to play game records the user actually has overall
 *  array       Results
 *   object      [value]
 *    int        ID                         unique identifier of the leaderboard
 *    string     Title                      name of the leaderboard
 *    int        Description                details about what the leaderboard is tracking
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

$leaderboard = Leaderboard::firstWhere('GameID', request()->query('i'));
if (!$leaderboard) {
    return response()->json([], 404);
}

$totalLeaderboards = Leaderboard::where('GameID', $leaderboard->game->id)
    ->count();

$results = Leaderboard::where('GameID', $leaderboard->game->id)
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($entry) {
        return [
            'ID' => $entry->ID,
            'Title' => $entry->Title,
            'Description' => $entry->Description,
        ];
    });

return response()->json([
    'Count' => count($results),
    'Total' => $totalLeaderboards,
    'Results' => $results,
]);