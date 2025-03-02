<?php

/*
 *  API_GetUsersFollowingMe - returns list of the caller's follower users
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *  int         Count                       number of user records returned in the response
 *  int         Total                       number of user records the user actually has overall
 *  array       Results
 *   object      [value]
 *    string     User                       username
 *    string     ULID                       queryable stable unique identifier of the user
 *    int        Points                     number of hardcore points the user has earned
 *    int        PointsSoftcore             number of softcore points the user has earned
 *    boolean    AmIFollowing               whether the caller user follows the follower user back
 */

use App\Community\Enums\UserRelationship;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'o' => 'nullable|integer|min:0',
    'c' => 'nullable|integer|min:1|max:500',
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

/** @var User $user */
$user = Auth::user();

$totalUsers = $user->followerUsers()
    ->whereNull('Deleted')
    ->count();

$usersList = $user
  ->followerUsers()
  ->whereNull("Deleted")
  ->with([
      "inverseRelatedUsers" => fn ($q) => $q
        ->select(sprintf("%s.ID", $user->getTable()), "related_user_id")
        ->where("user_id", $user->id)
        ->withPivot("Friendship"),
  ])
  ->orderByDesc("LastActivityID")
  ->skip($offset)
  ->take($count)
  ->get()
  ->map(
    fn ($followerUser) => [
        "User" => $followerUser->display_name,
        "ULID" => $followerUser->ulid,
        "Points" => $followerUser->points,
        "PointsSoftcore" => $followerUser->points_softcore,
        "AmIFollowing" => $followerUser->inverseRelatedUsers->first()?->pivot?->Friendship ===
          UserRelationship::Following,
    ]
  );

return response()->json([
    'Count' => count($usersList),
    'Total' => $totalUsers,
    'Results' => $usersList,
]);
