<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\MessageThread;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageThreadPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return false;
    }

    public function view(User $user, MessageThread $messageThread): bool
    {
        return true;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MessageThread $messageThread): bool
    {
        return false;
    }

    public function delete(User $user, MessageThread $messageThread): bool
    {
        return true;
    }

    public function restore(User $user, MessageThread $messageThread): bool
    {
        return false;
    }

    public function forceDelete(User $user, MessageThread $messageThread): bool
    {
        return false;
    }

    public function createForRecipient(User $user, User $targetUser): bool
    {
        $isUserDeveloper = $user->hasAnyRole([
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER_STAFF,
        ])
            || $user->getAttribute('Permissions') >= Permissions::JuniorDeveloper;

        /**
         * TODO check user privacy settings
         */
        if (!$isUserDeveloper) {
            if ($targetUser->only_allows_contact_from_followers && !$targetUser->isFollowing($user)) {
                return false;
            }
        }

        return true;
    }
}
