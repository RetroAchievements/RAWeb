<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Leaderboard;
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

$leaderboard = Leaderboard::find($lbID);
if (!$leaderboard) {
    abort(404);
}

// Only let jr. devs update their own leaderboards
if ($permissions == Permissions::JuniorDeveloper && $leaderboard->developer?->User !== $user) {
    abort(403);
}

$prevUpdated = $leaderboard->Updated;

if (submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)) {
    // if the leaderboard has entries and it's been at least 10 minutes since the last update, log an audit message
    if ($leaderboard->entries()->exists()) {
        $leaderboard->refresh();

        if ($leaderboard->Updated->diffInMinutes($prevUpdated) >= 10) {
            $commentText = 'made updates to this leaderboard';
            addArticleComment("Server", ArticleType::Leaderboard, $lbID, "\"$user\" $commentText.", $user);
        }
    }

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
