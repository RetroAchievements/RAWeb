<?php

 /*
 *  API_GetUserGameLeaderboards - returns a list of Leaderboards for the given User and GameID
 *    i : gameID
 *    u : username or user ULID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 200, max: 500)
 *
 *  int         Count                       number of leaderboard records returned in the response
 *  int         Total                       number of leaderboard records the user has actually on the game
 *  array       Results
 *   object      [value]
 *    int        ID                         unique identifier of the leaderboard
 *    boolean    RankAsc                    true if the leaderboard views a lower score as better, false otherwise
 *    string     Title                      the title of the leaderboard
 *    string     Description                the description of the leaderboard
 *    string     Format                     the format of the leaderboard (see: ValueFormat enum)
 *    object     UserEntry                  details of the requested user's leaderboard entry
 *     object      [value]
 *      string     User                     username
 *      string     ULID                     queryable stable unique identifier of the user
 *      int        Score                    raw value score
 *      string     FormattedScore           formatted string value of score
 *      int        Rank                     user's leaderboard rank
 *      string     DateUpdated              an ISO8601 timestamp string for when the entry was updated
 */

use App\Actions\FindUserByIdentifierAction;
use App\Models\Game;
use App\Models\LeaderboardEntry;
use App\Platform\Enums\ValueFormat;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'min:1'],
    'u' => ['required', new ValidUserIdentifier()],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 200;

$user = (new FindUserByIdentifierAction())->execute($input['u']);
if (!$user) {
    return response()->json(['User not found'], 404);
}

$game = Game::firstWhere("ID", request()->query('i'));
if (!$game) {
    return response()->json(['Game not found'], 404);
}

if ($game->leaderboards()->count() === 0) {
    return response()->json(['Game has no leaderboards'], 422);
}

$userLeaderboardEntriesCount = LeaderboardEntry::where('user_id', $user->id)
    ->join('UserAccounts', 'leaderboard_entries.user_id', '=', 'UserAccounts.ID')
    ->whereIn('leaderboard_id', function ($query) use ($game) {
        $query->select('ID')
            ->from('LeaderboardDef')
            ->where('GameID', $game->ID)
            ->whereNull('deleted_at');
    })
    ->whereNull('UserAccounts.unranked_at')
    ->where('UserAccounts.Untracked', 0)
    ->count();

if ($userLeaderboardEntriesCount === 0) {
    return response()->json(['User has no leaderboards on this game'], 422);
}

$leaderboardEntries = LeaderboardEntry::select('leaderboard_entries.*')
    ->addSelect([
        'calculated_rank' => LeaderboardEntry::from('leaderboard_entries as entries_rank_calc')
            ->join('LeaderboardDef as leaderboard_rank_calc', 'entries_rank_calc.leaderboard_id', '=', 'leaderboard_rank_calc.ID')
            ->join('UserAccounts', 'entries_rank_calc.user_id', '=', 'UserAccounts.ID')
            ->whereColumn('entries_rank_calc.leaderboard_id', 'leaderboard_entries.leaderboard_id')
            ->whereNull('UserAccounts.unranked_at')
            ->where('UserAccounts.Untracked', 0)
            ->where('entries_rank_calc.deleted_at', null)
            ->where('leaderboard_rank_calc.deleted_at', null)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('leaderboard_rank_calc.LowerIsBetter', 1)
                        ->whereColumn('entries_rank_calc.score', '<', 'leaderboard_entries.score');
                })->orWhere(function ($q) {
                    $q->where('leaderboard_rank_calc.LowerIsBetter', 0)
                        ->whereColumn('entries_rank_calc.score', '>', 'leaderboard_entries.score');
                });
            })
            ->selectRaw('COUNT(*) + 1'),
    ])
    ->join('LeaderboardDef', 'leaderboard_entries.leaderboard_id', '=', 'LeaderboardDef.ID')
    ->join('UserAccounts', 'leaderboard_entries.user_id', '=', 'UserAccounts.ID')
    ->where('LeaderboardDef.GameID', $game->ID)
    ->where('leaderboard_entries.user_id', $user->id)
    ->whereNull('UserAccounts.unranked_at')
    ->where('UserAccounts.Untracked', 0)
    ->whereNull('LeaderboardDef.deleted_at')
    ->with('leaderboard')
    ->orderBy('LeaderboardDef.ID', 'asc')
    ->skip($offset)
    ->take($count)
    ->get();

$results = [];
foreach ($leaderboardEntries as $leaderboardEntry) {
    $results[] = [
        'ID' => $leaderboardEntry->leaderboard->ID,
        'RankAsc' => boolval($leaderboardEntry->leaderboard->LowerIsBetter),
        'Title' => $leaderboardEntry->leaderboard->Title,
        'Description' => $leaderboardEntry->leaderboard->Description,
        'Format' => $leaderboardEntry->leaderboard->Format,
        'UserEntry' => [
            'User' => $user->display_name,
            'ULID' => $user->ulid,
            'Score' => $leaderboardEntry->score,
            'FormattedScore' => ValueFormat::format($leaderboardEntry->score, $leaderboardEntry->leaderboard->Format),
            'Rank' => $leaderboardEntry->calculated_rank,
            'DateUpdated' => $leaderboardEntry->updated_at->toIso8601String(),
        ],
    ];
}

return response()->json([
    'Count' => count($results),
    'Total' => $userLeaderboardEntriesCount,
    'Results' => $results,
]);
