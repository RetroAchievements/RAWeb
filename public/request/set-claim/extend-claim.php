<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
]);

$gameID = (int) $input['game'];

$userModel = User::firstWhere('User', $user);

if (extendClaim($userModel, $gameID)) { // Check that the claim was successfully added
    addArticleComment("Server", ArticleType::SetClaim, $gameID, "Claim extended by " . $user);

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
