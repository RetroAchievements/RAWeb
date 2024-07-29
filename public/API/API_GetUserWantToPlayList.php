<?php

/*
 *  API_GetUserWantToPlayList - returns a list of Games, with basic data, that a user has saved on their WantToPlayList
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
 *    int        PointsTotal                total points able to be earned
 *    int        AchievementsPublished      total number of achievements to be unlocked
 */

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Policies\UserGameListEntryPolicy;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
    return response()->json([], 404);
}

$policy = new UserGameListEntryPolicy();
$response = $policy->view(Auth::user(), $user);

if ($response->denied()) {
    return response()->json([], 401);
}

$totalWantToPlayItems = UserGameListEntry::where('user_id', $user->id)
    ->where('type', UserGameListType::Play)
    ->count();

$results = UserGameListEntry::where('user_id', $user->id)
    ->where('type', UserGameListType::Play)
    ->with(['game.system'])
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($entry) {
        $game = $entry->game;

        return [
            'ID' => $game->ID,
            'Title' => $game->Title,
            'ConsoleID' => $game->ConsoleID,
            'ConsoleName' => $game->system->name,
            'ImageIcon' => $game->ImageIcon,
            'PointsTotal' => $game->points_total,
            'AchievementsPublished' => $game->achievements_published,
        ];
    });

return response()->json([
    'Count' => count($results),
    'Total' => $totalWantToPlayItems,
    'Results' => $results,
]);
