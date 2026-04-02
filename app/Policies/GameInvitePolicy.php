<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameInvite;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;
use Illuminate\Auth\Access\Response;

class GameInvitePolicy
{
    /**
     * Determine if the user can view any game invites.
     */
    public function viewAny(User $user): Response
    {
        // All authenticated users can view their own invites
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to view game invites.');
    }

    /**
     * Determine if the user can view the game invite.
     */
    public function view(User $user, GameInvite $invite): Response
    {
        // Users can only see invites they sent or received
        return $user->id === $invite->sender_user_id || $user->id === $invite->recipient_user_id
            ? Response::allow()
            : Response::deny('You can only view your own game invites.');
    }

    /**
     * Determine if the user can create game invites.
     */
    public function create(User $user): Response
    {
        // All authenticated users can create invites
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to send game invites.');
    }

    /**
     * Determine if the user can update the game invite.
     */
    public function update(User $user, GameInvite $invite): Response
    {
        // Only allow status transitions by authorized users
        if (!$invite->canBeActedOnBy($user)) {
            return Response::deny('You cannot modify this game invite.');
        }

        // Check if the status transition is valid
        $newStatus = request()->input('status');
        if ($newStatus) {
            try {
                $statusEnum = GameInviteStatus::from($newStatus);
                if (!$invite->status->canTransitionTo($statusEnum)) {
                    return Response::deny('Invalid status transition.');
                }
            } catch (\ValueError $e) {
                return Response::deny('Invalid status value.');
            }
        }

        return Response::allow();
    }

    /**
     * Determine if the user can delete the game invite.
     */
    public function delete(User $user, GameInvite $invite): Response
    {
        // Only sender can delete pending invites (cancellation)
        if ($user->id === $invite->sender_user_id && $invite->status === GameInviteStatus::Pending) {
            return Response::allow();
        }

        return Response::deny('You can only cancel pending invites you sent.');
    }

    /**
     * Scope to only invites the user can see.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('sender_user_id', $user->id)
              ->orWhere('recipient_user_id', $user->id);
        });
    }
}
