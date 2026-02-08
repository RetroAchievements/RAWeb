<?php

declare(strict_types=1);

namespace App\Observers;

use App\Community\Actions\AddUserDiscordRolesAction;
use App\Community\Actions\RemoveUserDiscordRolesAction;
use App\Community\Enums\CommentableType;
use App\Community\Enums\ModerationActionType;
use App\Models\Comment;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Models\UserModerationAction;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function updating(User $user): void
    {
        $mutedRoleId = config('services.discord.muted');
        $actionedBy = Auth::user();

        // Handle muting (new mute or extension).
        if ($this->isBeingMuted($user) || $this->isMuteBeingExtended($user)) {
            // Only manipulate Discord roles on the first mute, not extensions.
            // Extensions just update the expiry. The user should already have only the Muted role.
            if ($mutedRoleId && $this->isBeingMuted($user)) {
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

            UserModerationAction::create([
                'user_id' => $user->id,
                'actioned_by_id' => $actionedBy?->id,
                'action' => ModerationActionType::Mute,
                'expires_at' => $user->muted_until,
            ]);
        }

        // Handle manual unmuting - remove the "Muted" role if they're a Discord member.
        if ($this->isBeingUnmuted($user)) {
            if ($mutedRoleId) {
                (new RemoveUserDiscordRolesAction())->execute($user, [$mutedRoleId]);
            }

            UserModerationAction::create([
                'user_id' => $user->id,
                'actioned_by_id' => $actionedBy?->id,
                'action' => ModerationActionType::Unmute,
            ]);
        }

        // Handle banning - remove all roles if they're a Discord member.
        // Banned users should also be unranked.
        if ($this->isBeingBanned($user)) {
            (new RemoveUserDiscordRolesAction())->execute($user);

            if ($user->unranked_at === null) {
                $user->unranked_at = now();
            }

            UserModerationAction::create([
                'user_id' => $user->id,
                'actioned_by_id' => $actionedBy?->id,
                'action' => ModerationActionType::Ban,
            ]);
        }

        // Handle unbanning.
        if ($this->isBeingUnbanned($user)) {
            UserModerationAction::create([
                'user_id' => $user->id,
                'actioned_by_id' => $actionedBy?->id,
                'action' => ModerationActionType::Unban,
            ]);
        }

        // Handle unranking.
        if ($this->isBeingUnranked($user) && !$this->isBeingBanned($user)) {
            UserModerationAction::create([
                'user_id' => $user->id,
                'actioned_by_id' => $actionedBy?->id,
                'action' => ModerationActionType::Unrank,
            ]);
        }

        // Handle reranking.
        if ($this->isBeingReranked($user)) {
            UserModerationAction::create([
                'user_id' => $user->id,
                'actioned_by_id' => $actionedBy?->id,
                'action' => ModerationActionType::Rerank,
            ]);
        }
    }

    /**
     * Use `updated` for search index changes. We only want to update the
     * index after the save is successful - not prematurely.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('banned_at')) {
            $this->syncSearchIndex($user, shouldIndex: $user->banned_at === null);
        }

        if ($user->wasChanged('is_user_wall_active')) {
            $wallComments = Comment::query()
                ->where('commentable_type', CommentableType::User)
                ->where('commentable_id', $user->id)
                ->get();

            $this->syncSearchIndex($wallComments, shouldIndex: $user->is_user_wall_active);
        }

        /**
         * We use the unranked_users table for efficiently filtering out
         * unranked users from certain queries. Let's be sure it's kept in sync.
         */
        if ($user->wasChanged('unranked_at')) {
            if ($user->unranked_at !== null) {
                UnrankedUser::firstOrCreate(['user_id' => $user->id]);
            } else {
                UnrankedUser::where('user_id', $user->id)->delete();
            }
        }
    }

    /**
     * Soft-deleted users should be unranked.
     */
    public function deleted(User $user): void
    {
        UnrankedUser::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * If a user is restored from being soft deleted, clean up the
     * unranked_users table if they shouldn't be unranked.
     */
    public function restored(User $user): void
    {
        if ($user->unranked_at === null) {
            UnrankedUser::where('user_id', $user->id)->delete();
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

        $originalMutedUntil = $user->getOriginal('muted_until');
        $oldMutedUntil = $originalMutedUntil instanceof DateTimeInterface
            ? Carbon::parse($originalMutedUntil)
            : null;

        /** @var Carbon|null $newMutedUntil */
        $newMutedUntil = $user->muted_until;

        $wasNotMuted = !$oldMutedUntil || $oldMutedUntil->isPast();
        $isNowMuted = $newMutedUntil !== null && $newMutedUntil->isFuture();

        return $wasNotMuted && $isNowMuted;
    }

    /**
     * Check if a user is transitioning from a muted to unmuted state.
     * This only triggers when the user is manually unmuted.
     */
    private function isBeingUnmuted(User $user): bool
    {
        if (!$user->isDirty('muted_until')) {
            return false;
        }

        $originalMutedUntil = $user->getOriginal('muted_until');
        $oldMutedUntil = $originalMutedUntil instanceof DateTimeInterface
            ? Carbon::parse($originalMutedUntil)
            : null;

        /** @var Carbon|null $newMutedUntil */
        $newMutedUntil = $user->muted_until;

        $wasMuted = $oldMutedUntil !== null && $oldMutedUntil->isFuture();
        $isNowUnmuted = $newMutedUntil === null || $newMutedUntil->isPast();

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

    /**
     * Check if an existing mute is being extended (both old and new are future dates).
     */
    private function isMuteBeingExtended(User $user): bool
    {
        if (!$user->isDirty('muted_until')) {
            return false;
        }

        $originalMutedUntil = $user->getOriginal('muted_until');
        $oldMutedUntil = $originalMutedUntil instanceof DateTimeInterface
            ? Carbon::parse($originalMutedUntil)
            : null;

        /** @var Carbon|null $newMutedUntil */
        $newMutedUntil = $user->muted_until;

        return $oldMutedUntil?->isFuture() === true && $newMutedUntil?->isFuture() === true;
    }

    /**
     * Check if a user is transitioning from a banned to unbanned state.
     */
    private function isBeingUnbanned(User $user): bool
    {
        if (!$user->isDirty('banned_at')) {
            return false;
        }

        $oldBannedAt = $user->getOriginal('banned_at');
        $newBannedAt = $user->banned_at;

        return $oldBannedAt !== null && $newBannedAt === null;
    }

    /**
     * Check if a user is transitioning from a ranked to unranked (untracked) state.
     */
    private function isBeingUnranked(User $user): bool
    {
        if (!$user->isDirty('unranked_at')) {
            return false;
        }

        $oldUnrankedAt = $user->getOriginal('unranked_at');
        $newUnrankedAt = $user->unranked_at;

        return !$oldUnrankedAt && $newUnrankedAt !== null;
    }

    /**
     * Check if a user is transitioning from an unranked (untracked) to ranked state.
     */
    private function isBeingReranked(User $user): bool
    {
        if (!$user->isDirty('unranked_at')) {
            return false;
        }

        $oldUnrankedAt = $user->getOriginal('unranked_at');
        $newUnrankedAt = $user->unranked_at;

        return $oldUnrankedAt !== null && $newUnrankedAt === null;
    }

    private function syncSearchIndex(mixed $searchable, bool $shouldIndex): void
    {
        if ($shouldIndex) {
            $searchable->searchable();
        } else {
            $searchable->unsearchable();
        }
    }
}
