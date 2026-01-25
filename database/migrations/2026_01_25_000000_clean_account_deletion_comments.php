<?php

declare(strict_types=1);

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const BODY_PATTERNS = [
        'request' => '%requested account deletion%',
        'cancel' => '%canceled account deletion%',
    ];

    public function up(): void
    {
        // Find all users who have more than 2 account deletion related moderation comments.
        $usersWithDuplicates = $this->accountDeletionCommentsQuery()
            ->select('commentable_id')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('commentable_id')
            ->having('cnt', '>', 2)
            ->pluck('commentable_id');

        foreach ($usersWithDuplicates as $userId) {
            $keepIds = $this->findBoundaryCommentIds($userId);

            if (empty($keepIds)) {
                continue;
            }

            // Soft delete all but the first and last pairs of these comments.
            $this->accountDeletionCommentsQuery()
                ->where('commentable_id', $userId)
                ->whereNotIn('id', $keepIds)
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // Restore all soft deleted account deletion comments.
        $this->accountDeletionCommentsQuery(withTrashed: true)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);
    }

    /**
     * Find the IDs of the first and last request/cancel comments for a user.
     * These boundary comments are preserved while intermediate duplicates are removed.
     *
     * @return array<int>
     */
    private function findBoundaryCommentIds(int $userId): array
    {
        $keepIds = [];

        foreach (self::BODY_PATTERNS as $pattern) {
            // Keep the first pair.
            $first = $this->accountDeletionCommentsQuery()
                ->where('commentable_id', $userId)
                ->where('body', 'like', $pattern)
                ->orderBy('created_at')
                ->value('id');

            // Keep the last pair.
            $last = $this->accountDeletionCommentsQuery()
                ->where('commentable_id', $userId)
                ->where('body', 'like', $pattern)
                ->orderByDesc('created_at')
                ->value('id');

            if ($first !== null) {
                $keepIds[] = $first;
            }
            if ($last !== null) {
                $keepIds[] = $last;
            }
        }

        return array_unique($keepIds);
    }

    /**
     * @return Builder<Comment>
     */
    private function accountDeletionCommentsQuery(bool $withTrashed = false): Builder
    {
        return Comment::query()
            ->when($withTrashed, fn ($q) => $q->withTrashed())
            ->where('commentable_type', 'user-moderation.comment')
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->where(function ($query) {
                $query->where('body', 'like', self::BODY_PATTERNS['request'])
                    ->orWhere('body', 'like', self::BODY_PATTERNS['cancel']);
            })
            ->when(!$withTrashed, fn ($q) => $q->whereNull('deleted_at'));
    }
};
