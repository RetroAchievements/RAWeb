<?php

/*
 *  API_GetUserSetRequests - gets user's set request list
 *    u : username
 *    t : type. 0 = only active, 1 = all requests including completed ones (default: 0)
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

use App\Community\Enums\UserGameListType;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required_without:i', 'min:2', 'max:20', new CtypeAlnum()],
    'i' => ['required_without:u', 'string', 'size:26'],
    't' => ['nullable', 'in:0,1'],
]);

$user = isset($input['i'])
    ? User::whereUlid($input['i'])->first()
    : User::whereName($input['u'])->first();
if (!$user) {
    return response()->json([], 404);
}

$type = (int) request()->query('t');

$query = UserGameListEntry::select([
    'GameData.id as GameID',
    'GameData.Title',
    'GameData.ImageIcon',
    'GameData.ConsoleID',
    'Console.Name as ConsoleName',
])
    ->join('GameData', 'SetRequest.GameID', '=', 'GameData.ID')
    ->join('Console', 'GameData.ConsoleID', '=', 'Console.ID')
    ->where('SetRequest.user_id', $user->id)
    ->where('type', UserGameListType::AchievementSetRequest);

if ($type !== 1) {
    $query->where('GameData.achievements_published', '=', '0');
}

$requestedSets = $query->orderBy('GameData.sort_title')->get()->toArray();

$userRequestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

return response()->json([
    'RequestedSets' => $requestedSets,
    'TotalRequests' => $userRequestInfo['total'],
    'PointsForNext' => $userRequestInfo['pointsForNext'],
]);
