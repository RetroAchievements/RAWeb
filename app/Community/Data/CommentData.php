<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Enums\CommentableType;
use App\Data\UserData;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Comment')]
class CommentData extends Data
{
    public function __construct(
        public int $id,
        public int $commentableId,
        public CommentableType $commentableType,
        public string $payload,
        public Carbon $createdAt,
        public ?Carbon $updatedAt,
        public UserData $user,
        public bool $canDelete,
        public bool $canReport,
        public bool $isAutomated,
        public ?string $url = null,
    ) {
    }

    public static function fromComment(Comment $comment): self
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return new self(
            id: $comment->id,
            commentableId: $comment->commentable_id,
            commentableType: $comment->commentable_type,
            payload: $comment->body,
            createdAt: Carbon::parse($comment->created_at),
            updatedAt: $comment->updated_at ? Carbon::parse($comment->updated_at) : null,
            user: UserData::fromUser($comment->user)->include('deletedAt'),
            canDelete: $currentUser ? $currentUser->can('delete', $comment) : false,
            canReport: $currentUser && $currentUser->can('createModerationReports', $currentUser) && $comment->user_id !== $currentUser->id,
            isAutomated: $comment->is_automated,
            url: $comment->url,
        );
    }

    /**
     * @param Collection<int, Comment> $comments
     * @return array<CommentData>
     */
    public static function fromCollection(Collection $comments): array
    {
        return array_map(
            fn ($comment) => self::fromComment($comment),
            $comments->all()
        );
    }
}
