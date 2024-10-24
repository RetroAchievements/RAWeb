<?php

declare(strict_types=1);

namespace App\Community\Data;

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
        public int $commentableType,
        public string $payload,
        public Carbon $createdAt,
        public ?Carbon $updatedAt,
        public UserData $user,
        public bool $canDelete,
    ) {
    }

    public static function fromComment(Comment $comment): self
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return new self(
            id: $comment->ID,
            commentableId: $comment->ArticleID,
            commentableType: $comment->ArticleType,
            payload: $comment->Payload,
            createdAt: Carbon::parse($comment->Submitted),
            updatedAt: $comment->Edited ? Carbon::parse($comment->Edited) : null,
            user: UserData::fromUser($comment->user)->include('deletedAt'),
            canDelete: $currentUser ? $currentUser->can('delete', $comment) : false,
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
