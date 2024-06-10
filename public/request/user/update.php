<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
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

$foundSourceUser = User::firstWhere('User', $user);
$foundTargetUser = User::firstWhere('User', $targetUsername);

if ($propertyType === UserAction::UpdatePermissions) {
    $response = SetAccountPermissionsJSON($foundSourceUser->User, $permissions, $targetUsername, $value);

    if ($response['Success']) {
        // Auto-apply forums permissions.
        if ($value >= Permissions::JuniorDeveloper && !$foundTargetUser->ManuallyVerified) {
            setAccountForumPostAuth($foundSourceUser, $permissions, $foundTargetUser, authorize: true);
        }

        // Adjust attached roles.
        if ($value <= Permissions::Unregistered) {
            $foundTargetUser->roles()->detach();
        } elseif ($value === Permissions::Registered) {
            $foundTargetUser->removeRole(Role::DEVELOPER_JUNIOR);
            $foundTargetUser->removeRole(Role::DEVELOPER);
            $foundTargetUser->removeRole(Role::MODERATOR);
        } elseif ($value === Permissions::JuniorDeveloper) {
            $foundTargetUser->removeRole(Role::DEVELOPER);
            $foundTargetUser->removeRole(Role::MODERATOR);

            $foundTargetUser->assignRole(Role::DEVELOPER_JUNIOR);
        } elseif ($value === Permissions::Developer) {
            $foundTargetUser->removeRole(Role::DEVELOPER_JUNIOR);
            $foundTargetUser->removeRole(Role::MODERATOR);

            $foundTargetUser->assignRole(Role::DEVELOPER);
        }
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::UpdateForumPostPermissions) {
    if (setAccountForumPostAuth($foundSourceUser, $permissions, $foundTargetUser, authorize: (bool) $value)) {
        return back()->with('success', __('legacy.success.ok'));
    }
}

if ($propertyType === UserAction::PatreonBadge) {
    $hasBadge = HasPatreonBadge($foundTargetUser);
    SetPatreonSupporter($foundTargetUser, !$hasBadge);

    if ($foundTargetUser) {
        addArticleComment(
            'Server',
            ArticleType::UserModeration,
            $foundTargetUser->id,
            $foundSourceUser->User . ($hasBadge ? ' revoked' : ' awarded') . ' Patreon badge'
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::LegendBadge) {
    $hasBadge = HasCertifiedLegendBadge($foundTargetUser);
    SetCertifiedLegend($foundTargetUser, !$hasBadge);

    if ($foundTargetUser) {
        addArticleComment(
            'Server',
            ArticleType::UserModeration,
            $foundTargetUser->id,
            $foundSourceUser->User . ($hasBadge ? ' revoked' : ' awarded') . ' Certified Legend badge'
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::TrackedStatus) {
    SetUserUntrackedStatus($targetUsername, $value);

    if ($foundTargetUser) {
        addArticleComment(
            'Server',
            ArticleType::UserModeration,
            $foundTargetUser->id,
            $foundSourceUser->User . ' set status to ' . ($value ? 'Untracked' : 'Tracked')
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
