<?php

/*
 *  API_GetUserSetRequests - gets user's set request list
 *    u : username
 *
 *  array      RequestedSets
 *    object     [value]
 *      int        GameID                     unique identifier of the game
 *      string     Title                      set title
 *      int        ConsoleID                  the id of the console that the set is for
 *      string     ConsoleName                the name of the console that the set is for
 *      string     ImageIcon                  the url of the sets icon
 *  int        TotalRequests              maximum number of requests a user has
 *  int        PointsForNext              number of points remaining until maximum request increase
 */

use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:1'],
]);

$user = User::firstWhere('User', request()->query('u'));
if (!$user) {
    return response()->json([], 404);
}

$requestedSets = UserGameListEntry::with(['game.system'])
    ->where('user_id', $user->ID)
    ->where('type', 'achievement_set_request')
    ->get()
    ->map(function ($gameData) {
        return [
            'GameID' => $gameData->game->id,
            'Title' => $gameData->game->title,
            'ConsoleID' => $gameData->game->system->id,
            'ConsoleName' => $gameData->game->system->name,
            'ImageIcon' => $gameData->game->ImageIcon,
        ];
    });

$userRequestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

return response()->json([
    'RequestedSets' => $requestedSets,
    'TotalRequests' => $userRequestInfo['total'],
    'PointsForNext' => $userRequestInfo['pointsForNext'],
]);
