<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Mail\RequestAccountDeleteMail;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RequestAccountDeletionAction
{
    public function execute(User $user): bool
    {
        // already requested account deletion
        if ($user->delete_requested_at) {
            return false;
        }

        // If the user has elevated permissions, drop them to Registered
        $currentPermissions = $user->getAttribute('Permissions');
        if ($currentPermissions > Permissions::Registered) {
            $user->setAttribute('Permissions', Permissions::Registered);

            $user->roles()->detach();
            $user->permissions()->detach();

            updateClaimsForPermissionChange($user, Permissions::Registered, $currentPermissions);
        }

        $user->delete_requested_at = Carbon::now();
        $user->saveQuietly();

        $this->cleanOldDeletionComments($user);

        addArticleComment('Server', CommentableType::UserModeration, $user->id,
            $user->display_name . ' requested account deletion'
        );

        Mail::to($user)->queue(new RequestAccountDeleteMail($user));

        return true;
    }

    /**
     * Soft delete old account deletion comments, keeping only the first pair.
     */
    private function cleanOldDeletionComments(User $user): void
    {
        $baseQuery = fn () => Comment::query()
            ->where('commentable_type', CommentableType::UserModeration)
            ->where('commentable_id', $user->id)
            ->where('user_id', Comment::SYSTEM_USER_ID);

        // Find the first pair to preserve.
        $firstRequestId = $baseQuery()
            ->where('body', 'like', '%requested account deletion%')
            ->orderBy('created_at')
            ->value('id');
        $firstCancelId = $baseQuery()
            ->where('body', 'like', '%canceled account deletion%')
            ->orderBy('created_at')
            ->value('id');

        $keepIds = array_filter([$firstRequestId, $firstCancelId]);
        if (empty($keepIds)) {
            return;
        }

        // Soft delete all account deletion comments except the first pair.
        $baseQuery()
            ->where(function ($query) {
                $query->where('body', 'like', '%requested account deletion%')
                    ->orWhere('body', 'like', '%canceled account deletion%');
            })
            ->whereNotIn('id', $keepIds)
            ->update(['deleted_at' => now()]);
    }
}
