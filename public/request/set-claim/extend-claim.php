<?php

use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
]);

$gameID = (int) $input['game'];

if (extendClaim($user, $gameID)) { // Check that the claim was successfully added
    addArticleComment("Server", ArticleType::SetClaim, $gameID, "Claim extended by " . $user);

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
