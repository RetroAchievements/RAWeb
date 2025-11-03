<?php

declare(strict_types=1);

namespace App\Observers;

use App\Community\Actions\AddUserDiscordRoleAction;
use App\Community\Actions\RemoveUserDiscordRoleAction;
use App\Http\Actions\RemoveDiscordRolesAction;
use App\Models\User;
use Carbon\Carbon;

class UserObserver
{
    public function updating(User $user): void
    {
        $mutedRoleId = config('services.discord.muted');

        // Handle muting - add the "Muted" role if they're a Discord member.
        if ($this->isBeingMuted($user) && $mutedRoleId) {
            (new AddUserDiscordRoleAction())->execute($user, $mutedRoleId);
        }

        // Handle manual unmuting - remove the "Muted" role if they're a Discord member.
        if ($this->isBeingUnmuted($user) && $mutedRoleId) {
            (new RemoveUserDiscordRoleAction())->execute($user, $mutedRoleId);
        }

        // Handle banning - remove all roles if they're a Discord member.
        if ($this->isBeingBanned($user)) {
            (new RemoveDiscordRolesAction())->execute($user);
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
