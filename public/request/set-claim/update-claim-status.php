<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, Permissions::Moderator)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'claim' => 'required|integer|exists:SetClaim,ID',
    'claim_status' => ['required', 'integer', Rule::in(ClaimStatus::cases())],
]);

$claim = AchievementSetClaim::find((int) $input['claim']);
if ($claim) {
    $claim->Status = (int) $input['claim_status'];
    $claim->save();

    addArticleComment(
        "Server",
        ArticleType::SetClaim,
        $claim->game_id,
        "{$user->display_name} updated " . $claim->user->display_name . "'s claim. Claim Status: " . ClaimStatus::toString($claim->Status)
    );

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
