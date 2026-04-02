<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LookingForGroupInvite;
use App\Models\User;
use App\Community\Enums\LookingForGroupInviteStatus;
use Illuminate\Auth\Access\Response;

class LookingForGroupInvitePolicy
{
    /**
     * Determine if the user can view any LFG invites.
     */
    public function viewAny(User $user): Response
    {
        // All verified users can view LFG invites (will be filtered to their own)
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to view LFG invites.');
    }

    /**
     * Determine if the user can view the LFG invite.
     */
    public function view(User $user, LookingForGroupInvite $invite): Response
    {
        // Users can only see invites they sent or received
        return $user->id === $invite->sender_user_id || $user->id === $invite->recipient_user_id
            ? Response::allow()
            : Response::deny('You can only view your own LFG invites.');
    }

    /**
     * Determine if the user can create LFG invites.
     */
    public function create(User $user): Response
    {
        // All verified users can create invites
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to send LFG invites.');
    }

    /**
     * Determine if the user can update the LFG invite.
     */
    public function update(User $user, LookingForGroupInvite $invite): Response
    {
        // Only allow updates by authorized users (sender or recipient)
        if (!$invite->canBeActedOnBy($user)) {
            return Response::deny('You cannot modify this LFG invite.');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can delete the LFG invite.
     */
    public function delete(User $user, LookingForGroupInvite $invite): Response
    {
        // Only sender can delete pending invites (cancellation)
        if ($user->id === $invite->sender_user_id && $invite->status === LookingForGroupInviteStatus::Pending) {
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
