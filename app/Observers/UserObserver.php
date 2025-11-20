<?php

declare(strict_types=1);

namespace App\Observers;

use App\Community\Actions\AddUserDiscordRolesAction;
use App\Community\Actions\RemoveUserDiscordRolesAction;
use App\Models\User;
use Carbon\Carbon;

class UserObserver
{
    public function updating(User $user): void
    {
        $mutedRoleId = config('services.discord.muted');

        // Handle muting - add the "Muted" role if they're a Discord member.
        if ($this->isBeingMuted($user) && $mutedRoleId) {
            /**
             * Remove _all_ the user's roles. Due to role precedence, this is the
             * only way we can ensure a user can't talk while having the Muted role.
             *
             * An ideal state would be to only remove the user's "Verified" role, and
             * then we'd add it back when the user is unmuted. However, we have no way
             * of tracking if the user was actually given the "Verified" role in the first
             * place, making this a potential vulnerability.
             *
             * TODO: In the future, users should be able to directly connect their Discord
             * account to RA, and we can use that to determine whether to add/remove only
             * the Verified role (in addition to the Muted) role on mute/unmute.
             */
            (new RemoveUserDiscordRolesAction())->execute($user);

            (new AddUserDiscordRolesAction())->execute($user, [$mutedRoleId]);
        }

        // Handle manual unmuting - remove the "Muted" role if they're a Discord member.
        if ($this->isBeingUnmuted($user) && $mutedRoleId) {
            (new RemoveUserDiscordRolesAction())->execute($user, [$mutedRoleId]);
        }

        // Handle banning - remove all roles if they're a Discord member.
        if ($this->isBeingBanned($user)) {
            (new RemoveUserDiscordRolesAction())->execute($user);
        }
    }

    /**
     * Check if a user is transitioning from an unmuted to muted state.
     */
    private function isBeingMuted(User $user): bool
    {
        if (!$user->isDirty('muted_until')) {
            return false;
        }

        $oldMutedUntil = $user->getOriginal('muted_until')
            ? Carbon::parse($user->getOriginal('muted_until'))
            : null;
        $newMutedUntil = $user->muted_until;

        $wasNotMuted = !$oldMutedUntil || $oldMutedUntil->isPast();
        $isNowMuted = $newMutedUntil && $newMutedUntil->isFuture();

        return $wasNotMuted && $isNowMuted;
    }

    /**
     * Check if a user is transitioning from a muted to unmuted state.
     */
    private function isBeingUnmuted(User $user): bool
    {
        if (!$user->isDirty('muted_until')) {
            return false;
        }

        $oldMutedUntil = $user->getOriginal('muted_until')
            ? Carbon::parse($user->getOriginal('muted_until'))
            : null;
        $newMutedUntil = $user->muted_until;

        $wasMuted = $oldMutedUntil && $oldMutedUntil->isFuture();
        $isNowUnmuted = !$newMutedUntil || $newMutedUntil->isPast();

        return $wasMuted && $isNowUnmuted;
    }

    /**
     * Check if a user is transitioning from an unbanned to banned state.
     */
    private function isBeingBanned(User $user): bool
    {
        if (!$user->isDirty('banned_at')) {
            return false;
        }

        $oldBannedAt = $user->getOriginal('banned_at');
        $newBannedAt = $user->banned_at;

        return !$oldBannedAt && $newBannedAt !== null;
    }
}
