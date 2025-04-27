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
    ) {
    }

    public static function fromForumTopicComment(ForumTopicComment $comment): self
    {
        return new self(
            id: $comment->id,
            body: html_entity_decode($comment->body),
            createdAt: $comment->created_at,
            updatedAt: $comment->updated_at,
            user: UserData::from($comment->user),
            isAuthorized: $comment->is_authorized,
            forumTopic: Lazy::create(fn () => ForumTopicData::from($comment->forumTopic)),
        );
    }
}
