<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ForumTopicComment;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ForumTopicComment')]
class ForumTopicCommentData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public Carbon $createdAt,
        public ?Carbon $updatedAt,
        public ?UserData $user,
        public bool $isAuthorized, // TODO migrate to $authorizedAt
        public ?int $forumTopicId = null, // TODO remove and use $forumTopic instead
        public Lazy|ForumTopicData|null $forumTopic = null,
        public Lazy|UserData|null $sentBy = null,
        public Lazy|UserData|null $editedBy = null,
    ) {
    }

    public static function fromForumTopicComment(ForumTopicComment $comment): self
    {
        return new self(
            id: $comment->id,
            body: $comment->body,
            createdAt: $comment->created_at,
            updatedAt: $comment->updated_at,
            user: UserData::from($comment->user),
            isAuthorized: $comment->is_authorized,
            forumTopicId: $comment->forum_topic_id,
            forumTopic: Lazy::create(fn () => $comment->forumTopic ? ForumTopicData::fromForumTopic($comment->forumTopic) : null),
            sentBy: Lazy::create(fn () => $comment->sent_by_id ? UserData::from($comment->sentBy) : null),
            editedBy: Lazy::create(fn () => $comment->edited_by_id ? UserData::from($comment->editedBy) : null),
        );
    }
}
