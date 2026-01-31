<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Number;

return new class extends Migration {
    private const REQUEST_PATTERN = '%requested account deletion%';
    private const CANCEL_PATTERN = '%canceled account deletion%';

    public function up(): void
    {
        $usersWithDuplicates = $this->baseQuery()
            ->select('commentable_id')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('commentable_id')
            ->having('cnt', '>', 2)
            ->pluck('commentable_id');

        foreach ($usersWithDuplicates as $userId) {
            $this->processUserComments($userId);
        }
    }

    public function down(): void
    {
        $this->baseQuery()
            ->withTrashed()
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);
    }

    private function processUserComments(int $userId): void
    {
        $requestCount = $this->userQuery($userId)->where('body', 'like', self::REQUEST_PATTERN)->count();
        $cancelCount = $this->userQuery($userId)->where('body', 'like', self::CANCEL_PATTERN)->count();

        $keepIds = $this->findBoundaryCommentIds($userId);
        if (empty($keepIds)) {
            return;
        }

        if ($requestCount > 1) {
            $this->appendOrdinalToLastComment($userId, self::REQUEST_PATTERN, $requestCount, 'request');
        }

        if ($cancelCount > 1) {
            $this->appendOrdinalToLastComment($userId, self::CANCEL_PATTERN, $cancelCount, 'cancellation');
        }

        $this->userQuery($userId)
            ->whereNotIn('id', $keepIds)
            ->update(['deleted_at' => now()]);
    }

    private function appendOrdinalToLastComment(int $userId, string $pattern, int $count, string $suffix): void
    {
        $lastComment = $this->userQuery($userId)
            ->where('body', 'like', $pattern)
            ->orderByDesc('created_at')
            ->first();

        if ($lastComment && !str_contains($lastComment->body, $suffix)) {
            $lastComment->body .= ' (' . Number::ordinal($count) . ' ' . $suffix . ')';
            $lastComment->save();
        }
    }

    /**
     * @return array<int>
     */
    private function findBoundaryCommentIds(int $userId): array
    {
        $keepIds = [];

        foreach ([self::REQUEST_PATTERN, self::CANCEL_PATTERN] as $pattern) {
            $query = $this->userQuery($userId)->where('body', 'like', $pattern);

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

    /**
     * @return Builder<Comment>
     */
    private function baseQuery(): Builder
    {
        return Comment::query()
            ->where('commentable_type', CommentableType::UserModeration)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->where(function ($query) {
                $query->where('body', 'like', self::REQUEST_PATTERN)
                    ->orWhere('body', 'like', self::CANCEL_PATTERN);
            });
    }

    /**
     * @return Builder<Comment>
     */
    private function userQuery(int $userId): Builder
    {
        return $this->baseQuery()->where('commentable_id', $userId);
    }
};
