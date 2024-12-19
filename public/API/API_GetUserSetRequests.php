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

use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;

$user = User::firstWhere('User', request()->query('u'));
if (!$user) {
    return response()->json([], 404);
}

$requestedSets = UserGameListEntry::where('user_id', $user->ID)
    ->where('type', 'achievement_set_request')
    ->pluck('GameID')
    ->toArray();

$games = Game::with('system')
    ->whereIn('ID', $requestedSets)
    ->get();

$userRequestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

$requestList = $games->map(function ($gameData) {
    return [
        'GameID' => $gameData['ID'],
        'Title' => $gameData['Title'],
        'ConsoleID' => $gameData['ConsoleID'],
        'ConsoleName' => $gameData->system['Name'],
        'ImageIcon' => $gameData['ImageIcon'],
    ];
})->toArray();

return response()->json([
    'RequestedSets' => $requestList,
    'TotalRequests' => $userRequestInfo['total'],
    'PointsForNext' => $userRequestInfo['pointsForNext'],
]);
