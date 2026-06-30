<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\UserGameListType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserGameListEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::MODERATOR,
            Role::ADMINISTRATOR,
        ]);
    }

    public function view(User $user, User $targetUser, UserGameListType $type): bool
    {
        return match ($type) {
            UserGameListType::Play => $this->viewPlayList($user, $targetUser),
            UserGameListType::AchievementSetRequest => $this->viewSetRequestList($user, $targetUser),

            /**
             * Nothing currently exposes another user's full Want to Develop list, so a
             * default deny here is the safe answer. The per-game "Developer Interest" page
             * is a different authorization question and is gated by GamePolicy::viewDeveloperInterest.
             */
            UserGameListType::Develop => false,
        };
    }

    private function viewPlayList(User $user, User $targetUser): bool
    {
        return $user->is($targetUser) || $user->isFriendsWith($targetUser);
    }

    private function viewSetRequestList(User $user, User $targetUser): bool
    {
        return true;
    }

    public function create(User $user, UserGameListType $type): bool
    {
        return $this->canAccessType($user, $type);
    }

    public function update(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }

    public function delete(User $user, UserGameListEntry $userGameListEntry): bool
    {
        if ($user->id !== $userGameListEntry->user_id) {
            return false;
        }

        return $this->canAccessType($user, $userGameListEntry->type);
    }

    public function restore(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }

    public function forceDelete(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }

    private function canAccessType(User $user, UserGameListType $type): bool
    {
        return match ($type) {
            UserGameListType::Play => true,
            UserGameListType::Develop => $user->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR]),

            // Set requests have their own controller with domain-specific validation.
            UserGameListType::AchievementSetRequest => false,
        };
    }
}
