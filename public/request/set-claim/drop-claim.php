<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'claim_type' => ['required', 'integer', Rule::in(ClaimType::cases())],
]);

$gameID = (int) $input['game'];
$claimType = (int) $input['claim_type'];

if (dropClaim($user, $gameID)) { // Check that the claim was successfully dropped
    if ($claimType == ClaimType::Primary) {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Primary claim dropped by {$user->display_name}");
    } else {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Collaboration claim dropped by {$user->display_name}");
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
