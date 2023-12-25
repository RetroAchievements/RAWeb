<?php

/*
 *  API_GetUserProfile
 *    u : username
 *
 *  string     User                    name of user
 *  int        ID                      unique identifier of the user
 *  int        TotalPoints             number of hardcore points the user has
 *  int        TotalSoftcorePoints     number of softcore points the user has
 *  int        TotalTruePoints         number of "white" points the user has
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

use App\Site\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
]);

$user = User::firstWhere('User', request()->query('u'));

if (!$user) {
    return response()->json([], 404);
}

return response()->json([
    'User' => $user->User,
    'UserPic' => sprintf("/UserPic/%s.png", $user->User),
    'MemberSince' => $user->Created?->toDateTimeString(),
    'RichPresenceMsg' => empty($user->RichPresenceMsg) || $user->RichPresenceMsg === 'Unknown' ? null : $user->RichPresenceMsg,
    'LastGameID' => $user->LastGameID,
    'ContribCount' => $user->ContribCount,
    'ContribYield' => $user->ContribYield,
    'TotalPoints' => $user->RAPoints,
    'TotalSoftcorePoints' => $user->RASoftcorePoints,
    'TotalTruePoints' => $user->TrueRAPoints,
    'Permissions' => $user->getAttribute('Permissions'),
    'Untracked' => $user->Untracked,
    'ID' => $user->ID,
    'UserWallActive' => $user->UserWallActive,
    'Motto' => $user->Motto,
]);
