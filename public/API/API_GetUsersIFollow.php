<?php

/*
 *  API_GetUsersIFollow - returns list of the caller's followed users
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of user records returned in the response
 *  int         Total                       number of user records the user actually has overall
 *  array       Results
 *   object      [value]
 *    string     User                       username
 *    int        Points                     number of hardcore points the user has earned
 *    int        PointsSoftcore             number of softcore points the user has earned
 *    boolean    IsFollowingMe              whether the followed user follows the caller user back
 */

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'o' => 'nullable|integer|min:0',
    'c' => 'nullable|integer|min:1|max:500',
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

/** @var User $user */
$user = Auth::user();

$totalUsers = $user->followedUsers()
    ->whereNull('Deleted')
    ->count();

$userTable = $user->getTable();
$friendTable = (new UserRelation())->getTable();
$friendshipStatus = UserRelationship::Following;

$usersList = $user->followedUsers()
    ->select([
        "$userTable.*",
        DB::raw("EXISTS (
            SELECT 1
            FROM $friendTable AS ur
            WHERE ur.user_id = $friendTable.related_user_id
            AND ur.related_user_id = $friendTable.user_id
            AND ur.Friendship = $friendshipStatus
        ) AS is_following_me"),
        "$friendTable.user_id as pivot_user_id",
        "$friendTable.related_user_id as pivot_related_user_id",
        "$friendTable.Friendship as pivot_Friendship",
    ])
    ->whereNull('Deleted')
    ->orderBy('LastActivityID', 'DESC')
    ->skip($offset)
    ->take($count)
    ->get()
    ->map(function ($followedUser) {
        return [
            'User' => $followedUser->display_name,
            'Points' => $followedUser->points,
            'PointsSoftcore' => $followedUser->points_softcore,
            'IsFollowingMe' => filter_var($followedUser->is_following_me, FILTER_VALIDATE_BOOLEAN),
        ];
    });

return response()->json([
    'Count' => count($usersList),
    'Total' => $totalUsers,
    'Results' => $usersList,
]);
