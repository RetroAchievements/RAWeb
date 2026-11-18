<?php

use App\Community\Actions\ProcessDeveloperRoleChangeAction;
use App\Community\Enums\CommentableType;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'target' => 'required|string|exists:users,display_name',
    'property' => ['required', 'integer', Rule::in(UserAction::cases())],
    'value' => 'required|integer',
]);

$targetUsername = $input['target'];
$propertyType = (int) $input['property'];
$value = (int) $input['value'];

$foundSourceUser = User::whereName($user)->first();
$foundTargetUser = User::whereName($targetUsername)->first();

if (!$foundSourceUser || !$foundTargetUser) {
    return back()->withErrors(__('legacy.error.error'));
}

if ($propertyType === UserAction::UpdatePermissions) {
    $priorDeveloperRole = match (true) {
        $foundTargetUser->hasRole(Role::DEVELOPER) => Role::DEVELOPER,
        $foundTargetUser->hasRole(Role::DEVELOPER_JUNIOR) => Role::DEVELOPER_JUNIOR,
        default => null,
    };

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

        $resultingDeveloperRole = match (true) {
            $value === Permissions::Developer => Role::DEVELOPER,
            $value === Permissions::JuniorDeveloper => Role::DEVELOPER_JUNIOR,
            $value <= Permissions::Registered => null,
            default => $priorDeveloperRole,
        };

        $foundTargetUser->refresh();
        (new ProcessDeveloperRoleChangeAction())->execute(
            $foundTargetUser,
            $priorDeveloperRole,
            $resultingDeveloperRole,
            $foundSourceUser,
        );
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::UpdateForumPostPermissions) {
    if (setAccountForumPostAuth($foundSourceUser, $permissions, $foundTargetUser, authorize: (bool) $value)) {
        return back()->with('success', __('legacy.success.ok'));
    }
}

if ($propertyType === UserAction::PatreonBadge) {
    Validator::validate(['value' => $value], [
        'value' => ['required', 'integer', Rule::in([1, 2])],
    ]);

    $comment = DB::transaction(function () use ($foundTargetUser, $value) {
        User::whereKey($foundTargetUser->id)->lockForUpdate()->first();

        $currentTier = GetPatreonSupporterTier($foundTargetUser);

        if ($value === $currentTier) {
            SetPatreonSupporter($foundTargetUser, 0);
            $foundTargetUser->removeRole(Role::SUPPORTER);

            return sprintf('revoked $%d Patreon supporter status', $currentTier);
        }

        SetPatreonSupporter($foundTargetUser, $value);

        if ($value === 2) {
            $foundTargetUser->assignRole(Role::SUPPORTER);
        } else {
            $foundTargetUser->removeRole(Role::SUPPORTER);
        }

        return sprintf('awarded $%d Patreon supporter status', $value);
    });

    addArticleComment(
        'Server',
        CommentableType::UserModeration,
        $foundTargetUser->id,
        $foundSourceUser->display_name . ' ' . $comment
    );

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::LegendBadge) {
    $hasBadge = HasCertifiedLegendBadge($foundTargetUser);
    SetCertifiedLegend($foundTargetUser, !$hasBadge);

    addArticleComment(
        'Server',
        CommentableType::UserModeration,
        $foundTargetUser->id,
        $foundSourceUser->display_name . ($hasBadge ? ' revoked' : ' awarded') . ' Certified Legend badge'
    );

    return back()->with('success', __('legacy.success.ok'));
}

if ($propertyType === UserAction::TrackedStatus) {
    SetUserUntrackedStatus($foundTargetUser, (bool) $value);

    addArticleComment(
        'Server',
        CommentableType::UserModeration,
        $foundTargetUser->id,
        $foundSourceUser->display_name . ' set status to ' . ($value ? 'Untracked' : 'Tracked')
    );

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
