<?php

/*
 *  API_GetUserProfile
 *    u : username or user ULID
 *
 *  string     User                    non-stable name of user
 *  int        ID                      unique identifier of the user
 *  string     ULID                    queryable stable unique identifier of the user
 *  int        TotalPoints             number of hardcore points the user has
 *  int        TotalSoftcorePoints     number of softcore points the user has
 *  int        TotalTruePoints         number of RetroPoints ("white points") the user has
 *  int        Permissions             unique identifier of user's account type
 *  datetime   MemberSince             when the user joined the site
 *  int        Untracked               "1" if the user is untracked, otherwise "0"
 *  string     UserPic                 site-relative path to the user's profile picture
 *  string     Motto                   the user's motto
 *  int        UserWallActive          "1" if the user allows comments to be posted to their wall, otherwise "0"
 *  int        LastGameID              unique identifier of the last game the user played
 *  string     RichPresenceMsg         activity information about the last game the user played
 *  int        ContribCount            achievements won by others
 *  int        ContribYield            points awarded to others
 */

use App\Actions\FindUserByIdentifierAction;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', new ValidUserIdentifier()],
]);

$user = (new FindUserByIdentifierAction())->execute($input['u']);
if (!$user) {
    return response()->json([], 404);
}

return response()->json([
    'User' => $user->display_name,
    'ULID' => $user->ulid,
    'UserPic' => sprintf("/UserPic/%s.png", $user->username),
    'MemberSince' => $user->created_at->toDateTimeString(),
    'RichPresenceMsg' => empty($user->rich_presence) || $user->rich_presence === 'Unknown' ? null : $user->rich_presence,
    'LastGameID' => $user->rich_presence_game_id,
    'ContribCount' => $user->yield_unlocks,
    'ContribYield' => $user->yield_points,
    'TotalPoints' => $user->points_hardcore,
    'TotalSoftcorePoints' => $user->points,
    'TotalTruePoints' => $user->points_weighted,
    'Permissions' => $user->getAttribute('Permissions'),
    'Untracked' => $user->unranked_at !== null,
    'ID' => $user->id,
    'UserWallActive' => $user->is_user_wall_active,
    'Motto' => $user->motto,
]);
