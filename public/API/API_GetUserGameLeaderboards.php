<?php

/*
 *  API_GetUserGameLeaderboards
 *    i : gameId
 *    u : username
 *
 *  array       Results
 *   object      [value]
 *    int        LeaderboardID              leaderboard ID
 *    string     LeaderboardName            leaderboard name
 *    string     LeaderboardDescription     leaderboard description
 *    bool       LeaderboardLowerIsBetter   whether the leaderboard score is better when lower
 *    int        Score                      raw value of the leaderboard entry's score
 *    string     FormattedScore             string value of the formatted leaderboard entry's score (reference GetGameLeaderboard for Format type)
 *    int        Rank                       user's leaderboard rank
 *    string     DateSubmitted              an ISO8601 timestamp string for when the entry was submitted
 */

use App\Models\Game;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'min:1'],
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
]);

$user = User::firstWhere('User', request()->query('u'));
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

$leaderboardEntries = LeaderboardEntry::select('leaderboard_entries.*')
    ->addSelect([
        'calculated_rank' => LeaderboardEntry::from('leaderboard_entries as entries_bis')
            ->join('LeaderboardDef as leaderboardDefBis', 'entries_bis.leaderboard_id', '=', 'leaderboardDefBis.ID')
            ->whereColumn('entries_bis.leaderboard_id', 'leaderboard_entries.leaderboard_id')
            ->whereNull('entries_bis.deleted_at')
            ->whereNull('leaderboardDefBis.deleted_at')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('leaderboardDefBis.LowerIsBetter', 1)
                      ->whereColumn('entries_bis.score', '<', 'leaderboard_entries.score');
                })->orWhere(function ($q) {
                    $q->where('leaderboardDefBis.LowerIsBetter', 0)
                      ->whereColumn('entries_bis.score', '>', 'leaderboard_entries.score');
                });
            })
            ->selectRaw('COUNT(*) + 1'),
    ])
    ->join('LeaderboardDef', 'leaderboard_entries.leaderboard_id', '=', 'LeaderboardDef.ID')
    ->where('LeaderboardDef.GameID', $game->ID)
    ->where('leaderboard_entries.user_id', $user->id)
    ->whereNull('leaderboard_entries.deleted_at')
    ->whereNull('LeaderboardDef.deleted_at')
    ->with('leaderboard')
    ->get();

$results = [];
foreach ($leaderboardEntries as $leaderboardEntry) {
    $results[] = [
        'LeaderboardID' => $leaderboardEntry->leaderboard->ID,
        'LeaderboardName' => $leaderboardEntry->leaderboard->Title,
        'LeaderboardDescription' => $leaderboardEntry->leaderboard->Description,
        'LeaderboardLowerIsBetter' => boolval($leaderboardEntry->leaderboard->LowerIsBetter),
        'Score' => $leaderboardEntry->score,
        'FormattedScore' => ValueFormat::format($leaderboardEntry->score, $leaderboardEntry->leaderboard->Format),
        'Rank' => $leaderboardEntry->calculated_rank,
        'DateSubmitted' => $leaderboardEntry->created_at->toIso8601String(),
    ];
}

return response()->json([
    'Results' => $results,
]);
