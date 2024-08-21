<?php

/*
 *  API_GetUserFriendList - returns a list of Friends, with basic data, for a user
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of friend records returned in the response
 *  int         Total                       number of friend records the user actually has overall
 *  array       Results
 *   object      [value]
 *    string     Friend                     username of the friend
 *    int        Points                     number of hardcore points the friend has earned
 *    int        PointsSoftcore             number of softcore points the friend has earned
 *    string     LastSeen                   rich presence message for the friend
 *    int        ID                         unique id of the friend
 */

use App\Enums\Permissions;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;
$user = Auth::user();

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
