<?php

/*
 *  API_GetFollowedUsersList - returns list of followed users
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of user records returned in the response
 *  int         Total                       number of user records the user actually has overall
 *  array       Results
 *   object      [value]
 *    string     User                       username
 *    int        Points                     number of hardcore points the user has earned
 *    int        PointsSoftcore             number of softcore points the user has earned
 *    string     LastSeen                   rich presence message for the user
 *    boolean    FollowsBack                whether the followed user follows the request user back
 *    int        ID                         unique id of the user
 */

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'c' => 'nullable|integer|min:0',
    'o' => 'nullable|integer|min:0',
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

/** @var User $user */
$user = Auth::user();

$totalUsers = $user->followedUsers()
    ->whereNull('Deleted')
    ->count();

$usersList = $user->followedUsers()
    ->whereNull('Deleted')
    ->orderBy('LastActivityID', 'DESC')
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($followedUser) use ($user) {
        return [
            'Friend' => $followedUser->User,
            'Points' => $followedUser->points,
            'PointsSoftcore' => $followedUser->points_softcore,
            'LastSeen' => empty($followedUser->RichPresenceMsg) ? 'Unknown' : strip_tags($followedUser->RichPresenceMsg),
            'FollowsBack' => $followedUser->isFollowing($user),
            'ID' => $followedUser->id,
        ];
    });

return response()->json([
    'Count' => count($usersList),
    'Total' => $totalUsers,
    'Results' => $usersList,
]);
