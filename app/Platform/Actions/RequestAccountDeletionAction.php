<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\Auth\RequestAccountDeleteNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class RequestAccountDeletionAction
{
    public function execute(User $user): bool
    {
        if ($user->delete_requested_at) {
            return false;
        }

        $this->revokeElevatedPermissions($user);

        $user->delete_requested_at = Carbon::now();
        $user->saveQuietly();

        // Count previous requests before cleanup so we can include it in the new comment.
        $previousRequestCount = Comment::withTrashed()
            ->accountDeletionForUser($user->id)
            ->where('body', 'like', '%requested account deletion%')
            ->count();

        $this->cleanOldDeletionComments($user);

        $commentBody = $user->display_name . ' requested account deletion';
        if ($previousRequestCount > 0) {
            $commentBody .= ' (' . Number::ordinal($previousRequestCount + 1) . ' request)';
        }

        addArticleComment('Server', CommentableType::UserModeration, $user->id, $commentBody);

        $user->notify(new RequestAccountDeleteNotification());

        return true;
    }

    /**
     * Soft delete old account deletion comments, keeping only the first and last pairs.
     */
    private function cleanOldDeletionComments(User $user): void
    {
        $keepIds = $this->findBoundaryCommentIds($user->id);
        if (empty($keepIds)) {
            return;
        }

        Comment::accountDeletionForUser($user->id)
            ->whereNotIn('id', $keepIds)
            ->update(['deleted_at' => now()]);
    }

    /**
     * @return array<int>
     */
    private function findBoundaryCommentIds(int $userId): array
    {
        $keepIds = [];

        foreach (['%requested account deletion%', '%canceled account deletion%'] as $pattern) {
            $query = Comment::accountDeletionForUser($userId)->where('body', 'like', $pattern);

            $first = (clone $query)->orderBy('created_at')->value('id');
            $last = (clone $query)->orderByDesc('created_at')->value('id');

            if ($first !== null) {
                $keepIds[] = $first;
            }
            if ($last !== null) {
                $keepIds[] = $last;
            }
        }

        return array_unique($keepIds);
    }

    private function revokeElevatedPermissions(User $user): void
    {
        $currentPermissions = $user->getAttribute('Permissions');
        if ($currentPermissions <= Permissions::Registered) {
            return;
        }

        $user->setAttribute('Permissions', Permissions::Registered);
        $user->roles()->detach();
        $user->permissions()->detach();

        updateClaimsForPermissionChange($user, Permissions::Registered, $currentPermissions);
    }
}
