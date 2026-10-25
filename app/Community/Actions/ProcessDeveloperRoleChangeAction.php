<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\AchievementMaintainer;
use App\Models\AchievementSetClaim;
use App\Models\Role;
use App\Models\User;

class ProcessDeveloperRoleChangeAction
{
    public function execute(
        User $user,
        ?string $developerRoleBefore,
        ?string $developerRoleAfter,
        ?User $actingUser = null,
    ): void {
        if ($developerRoleBefore === $developerRoleAfter) {
            return;
        }

        $permissionsName = Permissions::toString((int) $user->getAttribute('Permissions'));

        if ($developerRoleBefore === Role::DEVELOPER_JUNIOR && $developerRoleAfter === Role::DEVELOPER) {
            $this->activatePendingClaims($user, $permissionsName, $actingUser);
        }

        if ($developerRoleAfter === null) {
            $this->dropOpenClaims($user, $permissionsName, $actingUser);
        }

        if ($developerRoleBefore === Role::DEVELOPER) {
            $this->expireActiveMaintainerships($user);
        }
    }

    private function activatePendingClaims(User $user, string $permissionsName, ?User $actingUser): void
    {
        $claimComment = $actingUser
            ? "{$actingUser->display_name} updated {$user->display_name}'s claim via promotion to {$permissionsName}."
            : "{$user->display_name}'s claim updated via promotion to {$permissionsName}.";
        $claimComment .= ' Claim Status: ' . ClaimStatus::Active->label();

        $user->achievementSetClaims()
            ->inReview()
            ->get()
            ->each(function (AchievementSetClaim $pendingClaim) use ($claimComment): void {
                $pendingClaim->status = ClaimStatus::Active;
                $pendingClaim->save();

                addArticleComment('Server', CommentableType::SetClaim, $pendingClaim->game_id, $claimComment);
            });
    }

    private function dropOpenClaims(User $user, string $permissionsName, ?User $actingUser): void
    {
        $user->achievementSetClaims()
            ->activeOrInReview()
            ->get()
            ->each(function (AchievementSetClaim $openClaim) use ($user, $permissionsName, $actingUser): void {
                $openClaim->status = ClaimStatus::Dropped;
                $openClaim->save();

                $claimKindLabel = $openClaim->claim_type->label();
                $claimComment = $actingUser
                    ? "{$actingUser->display_name} dropped {$user->display_name}'s {$claimKindLabel} claim via demotion to {$permissionsName}."
                    : "{$user->display_name}'s {$claimKindLabel} claim dropped via demotion to {$permissionsName}.";

                addArticleComment('Server', CommentableType::SetClaim, $openClaim->game_id, $claimComment);
            });
    }

    private function expireActiveMaintainerships(User $user): void
    {
        AchievementMaintainer::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('effective_until')
            ->update([
                'is_active' => false,
                'effective_until' => now(),
            ]);
    }
}
