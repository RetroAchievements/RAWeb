<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RA\ArticleType;
use RA\ClaimType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'claim_type' => ['required', 'integer', Rule::in(ClaimType::cases())],
]);

$gameID = (int) $input['game'];
$claimType = (int) $input['claim_type'];

if (dropClaim($user, $gameID)) { // Check that the claim was successfully dropped
    if ($claimType == ClaimType::Primary) {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Primary claim dropped by " . $user);
    } else {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Collaboration claim dropped by " . $user);
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
