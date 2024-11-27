<?php

/*
 *  API_GetFollowerUsersList - returns list of follower users
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
 *    boolean    FollowingBack              whether the request user follows the follower user back
 *    int        ID                         unique id of the user
 */

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'c' => 'nullable|integer|min:0',
    'o' => 'nullable|integer|min:1|max:500',
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

/** @var User $user */
$user = Auth::user();

$totalUsers = $user->followerUsers()
    ->whereNull('Deleted')
    ->count();

$usersList = $user->followerUsers()
    ->whereNull('Deleted')
    ->orderBy('LastActivityID', 'DESC')
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($followerUser) use ($user) {
        return [
            'Friend' => $followerUser->User,
            'Points' => $followerUser->points,
            'PointsSoftcore' => $followerUser->points_softcore,
            'LastSeen' => empty($followerUser->RichPresenceMsg) ? 'Unknown' : strip_tags($followerUser->RichPresenceMsg),
            'FollowingBack' => $user->isFollowing($followerUser),
            'ID' => $followerUser->id,
        ];
    });

return response()->json([
    'Count' => count($usersList),
    'Total' => $totalUsers,
    'Results' => $usersList,
]);
