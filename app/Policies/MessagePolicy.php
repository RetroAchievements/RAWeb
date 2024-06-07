<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return false;
    }

    public function view(User $user, Message $message): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isNotMuted();
    }

    public function update(User $user, Message $message): bool
    {
        return false;
    }

    public function delete(User $user, Message $message): bool
    {
        return false;
    }

    public function restore(User $user, Message $message): bool
    {
        return false;
    }

    public function forceDelete(User $user, Message $message): bool
    {
        return false;
    }

    public function sendToRecipient(User $user, User $targetUser): bool
    {
        $canUserAlwaysSend = $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::EVENT_MANAGER,
            Role::FORUM_MANAGER,
            Role::MODERATOR,
            Role::POINT_OF_CONTACT,
        ])
            || $user->getAttribute('Permissions') >= Permissions::JuniorDeveloper;

        /**
         * TODO check user privacy settings
         */
        if (!$canUserAlwaysSend) {
            if ($targetUser->only_allows_contact_from_followers && !$targetUser->isFollowing($user)) {
                return false;
            }
        }

        return true;
    }
}
