<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RA\ArticleType;
use RA\Permissions;
use RA\UserAction;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'target' => 'required|string|exists:mysql_legacy.UserAccounts,User',
    'property' => ['required', 'integer', Rule::in(UserAction::cases())],
    'value' => 'required|integer',
]);

$targetUser = $input['target'];
$propertyType = (int) $input['property'];
$value = (int) $input['value'];

if ($propertyType === UserAction::UpdatePermissions) {
    $response = SetAccountPermissionsJSON($user, $permissions, $targetUser, $value);

    // auto-apply forums permissions
    if ($response['Success']
        && $value >= Permissions::JuniorDeveloper
        && !getUserForumPostAuth($targetUser)) {
        setAccountForumPostAuth($user, $permissions, $targetUser, authorize: true);
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::UpdateForumPostPermissions) {
    if (setAccountForumPostAuth($user, $permissions, $targetUser, authorize: (bool) $value)) {
        return back()->with('success', __('legacy.success.ok'));
    }
}

if ($propertyType === UserAction::PatreonBadge) {
    $hasBadge = HasPatreonBadge($targetUser);
    SetPatreonSupporter($targetUser, !$hasBadge);

    if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ($hasBadge ? ' revoked' : ' awarded') . ' Patreon badge'
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::LegendBadge) {
    $hasBadge = HasCertifiedLegendBadge($targetUser);
    SetCertifiedLegend($targetUser, !$hasBadge);
        if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ($hasBadge ? ' revoked' : ' awarded') . ' Certified Legend badge'
        );
    }
        return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::TrackedStatus) {
    SetUserUntrackedStatus($targetUser, $value);

    if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ' set status to ' . ($value ? 'Untracked' : 'Tracked')
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
