<?php

/*
 *  API_GetUserWantToPlayList - returns a list of GameIDs that a user has saved on their WantToPlayList
 *    u : username
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of want to play game records returned in the response
 *  int         Total                       number of want to play game records the user actually has overall
 *  array       Results
 *   object      [value]
 *    int        ID                         unique identifier of the game
 *    string     Title                      name of the game
 *    int        ConsoleID                  unique identifier of the console associated to the game
 *    string     ConsoleName                name of the console associated to the game
 *    string     ImageIcon                  site-relative path to the game's icon image
 *    int        TotalPoints                     total points able to be earned
 *    int        NumPossibleAchievements            total number of achievements to be unlocked
 */

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$user = User::firstWhere('User', request()->query('u'));
if (!$user) {
    return response()->json([]);
}

$wantToPlayGameIDs = UserGameListEntry::where('user_id', $user->id)
    ->where('type', UserGameListType::Play)
    ->pluck('GameID')
    ->toArray();

$pagedResults = array_slice($wantToPlayGameIDs, $offset, $count);

$results = [];

if (!empty($pagedResults)) {
    foreach ($pagedResults as $nextGameID) {
        $game = Game::with('system')->find($nextGameID);
        if ($game) {
            $gameData = [
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $game->ConsoleID,
                'ImageIcon' => $game->ImageIcon,
                'PointsTotal' => $game->points_total,
                'AchievementsPublished' => $game->achievements_published,
            ];

            array_push($results, $gameData);
        }
    }
}

return response()->json([
    'Count' => count($results),
    'Total' => count($wantToPlayGameIDs),
    'Results' => $results,
]);
