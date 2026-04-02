<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LookingForGroupPost;
use App\Models\User;
use App\Community\Enums\LookingForGroupStatus;
use Illuminate\Auth\Access\Response;

class LookingForGroupPostPolicy
{
    /**
     * Determine if the user can view any LFG posts.
     */
    public function viewAny(User $user): Response
    {
        // All verified users can view LFG posts
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to view LFG posts.');
    }

    /**
     * Determine if the user can view the LFG post.
     */
    public function view(User $user, LookingForGroupPost $post): Response
    {
        // All authenticated users can view LFG posts
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to view LFG posts.');
    }

    /**
     * Determine if the user can create LFG posts.
     */
    public function create(User $user): Response
    {
        // All verified users can create LFG posts
        return $user->isVerified()
            ? Response::allow()
            : Response::deny('You must be a verified user to create LFG posts.');
    }

    /**
     * Determine if the user can update the LFG post.
     */
    public function update(User $user, LookingForGroupPost $post): Response
    {
        // Only the creator can update their own posts
        if ($user->id !== $post->creator_user_id) {
            return Response::deny('You can only edit your own LFG posts.');
        }

        // Can only update active posts
        if ($post->status !== LookingForGroupStatus::Active) {
            return Response::deny('You can only edit active LFG posts.');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can delete the LFG post.
     */
    public function delete(User $user, LookingForGroupPost $post): Response
    {
        // Only the creator can delete their own posts
        if ($user->id !== $post->creator_user_id) {
            return Response::deny('You can only delete your own LFG posts.');
        }

        return Response::allow();
    }

    /**
     * Scope to only posts the user can see.
     */
    public function scopeForUser($query, User $user)
    {
        // All verified users can see all posts
        if ($user->isVerified()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }
}
