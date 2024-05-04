<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($sourceUser, $permissions, Permissions::Moderator)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'target' => 'required|string|exists:UserAccounts,User',
    'property' => ['required', 'integer', Rule::in(UserAction::cases())],
    'value' => 'required|integer',
]);

$targetUsername = $input['target'];
$propertyType = (int) $input['property'];
$value = (int) $input['value'];

$foundTargetUser = User::firstWhere('User', $targetUsername);
if (!$foundTargetUser) {
    return back()->withErrors(__('legacy.error.error'));
}

if ($propertyType === UserAction::UpdatePermissions) {
    $response = SetAccountPermissionsJSON($sourceUser->username, $permissions, $foundTargetUser->username, $value);

    // auto-apply forums permissions
    if (
        $response['Success']
        && $value >= Permissions::JuniorDeveloper
        && !$foundTargetUser->ManuallyVerified
    ) {
        setAccountForumPostAuth($sourceUser, $permissions, $foundTargetUser, authorize: true);
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::UpdateForumPostPermissions) {
    if (setAccountForumPostAuth($sourceUser, $permissions, $foundTargetUser, authorize: (bool) $value)) {
        return back()->with('success', __('legacy.success.ok'));
    }
}

if ($propertyType === UserAction::PatreonBadge) {
    $hasBadge = HasPatreonBadge($foundTargetUser->username);
    SetPatreonSupporter($foundTargetUser, !$hasBadge);

    addArticleComment(
        'Server',
        ArticleType::UserModeration,
        $foundTargetUser->id,
        $sourceUser->display_name . ($hasBadge ? ' revoked' : ' awarded') . ' Patreon badge'
    );

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::LegendBadge) {
    $hasBadge = HasCertifiedLegendBadge($foundTargetUser->username);
    SetCertifiedLegend($foundTargetUser, !$hasBadge);

    addArticleComment(
        'Server',
        ArticleType::UserModeration,
        $foundTargetUser->id,
        $sourceUser->display_name . ($hasBadge ? ' revoked' : ' awarded') . ' Certified Legend badge'
    );

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::TrackedStatus) {
    SetUserUntrackedStatus($foundTargetUser->username, $value);

    addArticleComment(
        'Server',
        ArticleType::UserModeration,
        $foundTargetUser->id,
        $sourceUser->display_name . ' set status to ' . ($value ? 'Untracked' : 'Tracked')
    );

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
