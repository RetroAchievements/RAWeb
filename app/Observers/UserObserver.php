<?php

declare(strict_types=1);

namespace App\Observers;

use App\Http\Actions\RemoveDiscordRolesAction;
use App\Models\User;
use Carbon\Carbon;

class UserObserver
{
    public function updating(User $user): void
    {
        // Bail as soon as we can if it's not an update we care to observe.

        if ($user->isDirty('muted_until') || $user->isDirty('banned_at')) {
            if ($this->isBeingMuted($user) || $this->isBeingBanned($user)) {
                (new RemoveDiscordRolesAction())->execute($user);
            }
        }

        // TODO handle other fields here
    }

    /**
     * Check if a user is transitioning from unmuted to muted state.
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
     * Check if a user is transitioning from unbanned to banned state.
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
