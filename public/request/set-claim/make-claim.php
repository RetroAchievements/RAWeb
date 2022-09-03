<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RA\ArticleType;
use RA\ClaimSetType;
use RA\ClaimType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'claim_type' => ['required', 'integer', Rule::in(ClaimType::cases())],
    'set_type' => ['required', 'integer', Rule::in(ClaimSetType::cases())],
    'create_topic' => 'sometimes|boolean',
]);

$gameID = (int) $input['game'];
$claimType = (int) $input['claim_type'];
$setType = (int) $input['set_type'];
$createForumTopic = (bool) ($input['create_topic'] ?? false);

$special = (int) checkIfSoleDeveloper($user, $gameID);
if (insertClaim($user, $gameID, $claimType, $setType, $special, (int) $permissions)) {
    addArticleComment("Server", ArticleType::SetClaim, $gameID, ClaimType::toString($claimType) . " " . ($setType == ClaimSetType::Revision ? "revision" : "") . " claim made by " . $user);

    if ($createForumTopic && $permissions >= Permissions::Developer) {
        generateGameForumTopic($user, $gameID, $forumTopicID);

        return redirect(route('game.show', $gameID));
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
