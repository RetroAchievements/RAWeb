<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Community\Enums\CommentableType;
use App\Models\Comment;

trait GeneratesLegacyAuditComment
{
    protected function addLegacyAuditComment(CommentableType $commentableType, int $commentableId, string $payload): void
    {
        $comment = Comment::create([
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $payload,
        ]);

        informAllSubscribersAboutActivity($commentableType, $commentableId, $this->user, $comment->id);
    }
}
