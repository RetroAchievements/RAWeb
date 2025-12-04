<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Models\Comment;

trait GeneratesLegacyAuditComment
{
    protected function addLegacyAuditComment(int $articleType, int $articleId, string $comment): void
    {
        $comment = Comment::create([
            'ArticleType' => $articleType,
            'ArticleID' => $articleId,
            'user_id' => Comment::SYSTEM_USER_ID,
            'Payload' => $comment,
        ]);

        informAllSubscribersAboutActivity($articleType, $articleId, $this->user, $comment->ID);
    }
}
