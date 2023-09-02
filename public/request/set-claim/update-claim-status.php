<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Community\Models\AchievementSetClaim;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
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

    print_r($claim->toArray());
    addArticleComment("Server", ArticleType::SetClaim, $claim->GameID,
        "$user updated " . $claim->User . "'s claim. Claim Status: " . ClaimStatus::toString($claim->Status));

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
