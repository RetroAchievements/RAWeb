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
 *    string     Title                      name of the leaderboard
 *    int        Description                details about what the leaderboard is tracking
 *    string     CurrentLeader              name of the user that is currently at the top of the leaderboard
 */

use App\Models\Leaderboard;
use App\Models\Game;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use App\Platform\Enums\ValueFormat;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'min:1', new CtypeAlnum()],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$gameId = request()->query('i');

$game = Game::firstWhere("ID", $gameId);

$leaderboards = $game->leaderboards()
    ->with('game')
    ->with('developer')
    ->skip($offset)
    ->take($count)
    ->get();

if (!$leaderboards) {
    return response()->json([], 404);
}

$results = [];
foreach ($leaderboards as $leaderboard) {

    $fetchedLeaderboardData = GetLeaderboardData($leaderboard, null, $count, $offset);
    $fetchedTopEntry = $fetchedLeaderboardData['Entries'][0];

    $topEntry = new stdClass();
    $topEntry->User = $fetchedTopEntry['User'];
    $topEntry->FormattedScore = ValueFormat::format($fetchedTopEntry['Score'], $leaderboard->Format);

    $results[] = [
        'ID' => $fetchedLeaderboardData['LBID'],
        'GameID' => $fetchedLeaderboardData['GameID'],
        'RankAsc' => boolval($fetchedLeaderboardData['LowerIsBetter']),
        'Title' => $fetchedLeaderboardData['LBTitle'],
        'Description' => $fetchedLeaderboardData['LBDesc'],
        'Format' => $fetchedLeaderboardData['LBFormat'],
        'TotalEntries' => $fetchedLeaderboardData['TotalEntries'],
        'TopEntry' => $topEntry,
    ];
}

return response()->json($results);
