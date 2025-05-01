<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
use App\Models\AchievementMaintainer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'target' => 'required|string|exists:UserAccounts,display_name',
    'property' => ['required', 'integer', Rule::in(UserAction::cases())],
    'value' => 'required|integer',
]);

$targetUsername = $input['target'];
$propertyType = (int) $input['property'];
$value = (int) $input['value'];

$foundSourceUser = User::whereName($user)->first();
$foundTargetUser = User::whereName($targetUsername)->first();

if ($propertyType === UserAction::UpdatePermissions) {
    $response = SetAccountPermissionsJSON($foundSourceUser->display_name, $permissions, $targetUsername, $value);

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
            $foundTargetUser->removeRole(Role::DEV_COMPLIANCE);
            $foundTargetUser->removeRole(Role::QUALITY_ASSURANCE);
            $foundTargetUser->removeRole(Role::CODE_REVIEWER);
            $foundTargetUser->removeRole(Role::MODERATOR);
        } elseif ($value === Permissions::JuniorDeveloper) {
            $foundTargetUser->removeRole(Role::DEVELOPER);
            $foundTargetUser->removeRole(Role::DEV_COMPLIANCE);
            $foundTargetUser->removeRole(Role::QUALITY_ASSURANCE);
            $foundTargetUser->removeRole(Role::CODE_REVIEWER);
            $foundTargetUser->removeRole(Role::MODERATOR);

            $foundTargetUser->assignRole(Role::DEVELOPER_JUNIOR);
        } elseif ($value === Permissions::Developer) {
            $foundTargetUser->removeRole(Role::DEVELOPER_JUNIOR);
            $foundTargetUser->removeRole(Role::MODERATOR);

            $foundTargetUser->assignRole(Role::DEVELOPER);
        }

        // If the developer is demoted, maintainership goes back to the original author(s).
        if ($value <= Permissions::Developer) {
            AchievementMaintainer::query()
                ->where('user_id', $foundTargetUser->id)
                ->where('is_active', true)
                ->whereNull('effective_until')
                ->update([
                    'is_active' => false,
                    'effective_until' => now(),
                ]);
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
            $foundSourceUser->display_name . ($hasBadge ? ' revoked' : ' awarded') . ' Patreon badge'
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
            $foundSourceUser->display_name . ($hasBadge ? ' revoked' : ' awarded') . ' Certified Legend badge'
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::TrackedStatus) {
    SetUserUntrackedStatus($foundTargetUser, $value);

    if ($foundTargetUser) {
        addArticleComment(
            'Server',
            ArticleType::UserModeration,
            $foundTargetUser->id,
            $foundSourceUser->display_name . ' set status to ' . ($value ? 'Untracked' : 'Tracked')
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
