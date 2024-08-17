<?php

/*
 *  API_GetUserFriendList - returns a list of Games, with basic data, that a user has saved on their WantToPlayList
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

use App\Enums\Permissions;
use App\Models\User;
use App\Policies\UserPolicy;
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

$policy = new UserPolicy();
if (!$policy->viewFriends(Auth::user(), $user)) {
    return response()->json([], 401);
}

$totalFriends = $user->friends()
    ->where('Permissions', '>=', Permissions::Unregistered)
    ->whereNull('Deleted')
    ->count();

$friendList = $user->friends()
    ->where('Permissions', '>=', Permissions::Unregistered)
    ->whereNull('Deleted')
    ->orderBy('LastActivityID', 'DESC')
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($friend) {
        return [
            'Friend' => $friend->User,
            'Points' => $friend->points,
            'PointsSoftcore' => $friend->points_softcore,
            'LastSeen' => empty($friend->RichPresenceMsg) ? 'Unknown' : strip_tags($friend->RichPresenceMsg),
            'ID' => $friend->id,
        ];
    });

return response()->json([
    'Count' => count($friendList),
    'Total' => $totalFriends,
    'Results' => $friendList,
]);
