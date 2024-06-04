<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'leaderboard' => 'required|integer',
    'trigger' => 'required',
    'title' => 'required',
    'description' => 'required',
    'format' => 'required',
    'lowerIsBetter' => 'required',
    'order' => 'required|integer',
]);

$lbID = $input['leaderboard'];
$lbMem = $input['trigger'];
$lbTitle = $input['title'];
$lbDescription = $input['description'];
$lbFormat = $input['format'];
$lbLowerIsBetter = $input['lowerIsBetter'];
$lbDisplayOrder = $input['order'];

$prevData = GetLeaderboardData($lbID, $user, 1, 0);
$prevUpdated = strtotime($prevData["LBUpdated"]);

// Only let jr. devs update their own leaderboards
if ($permissions == Permissions::JuniorDeveloper && $prevData["LBAuthor"] != $user) {
    abort(403);
}

if (submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)) {
    $updatedData = GetLeaderboardData($lbID, $user, 2, 0);
    $updated = strtotime($updatedData['LBUpdated']);
    $dateDiffMins = ($updated - $prevUpdated) / 60;

    if (!empty($updatedData['Entries'])) {
        if ($dateDiffMins > 10) {
            $commentText = 'edited this leaderboard';
            addArticleComment("Server", ArticleType::Leaderboard, $lbID, "\"$user\" $commentText.", $user);
        }
    }

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
