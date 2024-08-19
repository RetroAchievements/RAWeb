<?php

/*
 *  API_GetGameLeaderboards - returns a list of Leaderboards for the given GameID
 *    i : gameID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of leaderboard records returned in the response
 *  int         Total                       number of leaderboard records the game actually has overall
 *  array       Results
 *   object      [value]
 *    int        ID                         unique identifier of the leaderboard
 *    string     RankAsc                    string value of true or false for if the leaderboard views a lower score as better
 *    string     Title                      the title of the leaderboard
 *    string     Description                the description of the leaderboard
 *    string     Format                     the format of the leaderboard (see: ValueFormat enum)
 *    object     TopEntry                   details of the current leader
 *     object      [value]
 *      string     User                     username of the current leader
 *      int        Score                    raw value of current leader's score
 *      string     FormattedScore           formatted string value of current leader's score
 */

use App\Models\Game;
use App\Platform\Enums\ValueFormat;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'min:1'],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$gameId = request()->query('i');

$game = Game::firstWhere("ID", $gameId);

if (!$game) {
    return response()->json([], 404);
}

$leaderboards = $game->leaderboards()
    ->with('game')
    ->with('developer')
    ->withTopEntry()
    ->visible()
    ->skip($offset)
    ->take($count)
    ->get();

if (!$leaderboards) {
    return response()->json([], 404);
}

$results = [];
foreach ($leaderboards as $leaderboard) {
    $topEntry = null;

    if ($leaderboard->topEntry) {
        $topEntry = [
            'User' => $leaderboard->topEntry->user->User,
            'Score' => $leaderboard->topEntry->score,
            'FormattedScore' => ValueFormat::format($leaderboard->topEntry->score, $leaderboard->Format),
        ];
    }

    $results[] = [
        'ID' => $leaderboard->ID,
        'RankAsc' => boolval($leaderboard->LowerIsBetter),
        'Title' => $leaderboard->Title,
        'Description' => $leaderboard->Description,
        'Format' => $leaderboard->Format,
        'TopEntry' => $topEntry,
    ];
}

return response()->json([
    'Count' => count($leaderboards),
    'Total' => $game->leaderboards()->count(),
    'Results' => $results,
]);
