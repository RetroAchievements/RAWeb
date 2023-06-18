<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'existing_forum_topic' => 'required|integer|exists:mysql_legacy.ForumTopic,ID',
]);

// The user must have a claim on this game to become the OP of the game's forum topic.
$hasGameClaimed = false;
$claimData = getClaimData((int) $input['game'], true);
$claimListLength = count($claimData);
if ($claimListLength > 0 && $claimData[0]['ClaimType'] == ClaimType::Primary) {
    foreach ($claimData as $claim) {
        if (isset($claim['User']) && $claim['User'] === $user) {
            $hasGameClaimed = true;
        }
    }
}

if ($hasGameClaimed && updateTopicOriginalPoster($user, (int) $input['existing_forum_topic'])) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
