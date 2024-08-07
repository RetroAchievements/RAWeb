<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ForumTopic;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ForumTopic')]
class ForumTopicData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public Carbon $createdAt,
        public ?UserData $user = null,
        public Lazy|ForumTopicCommentData $latestComment,
        public Lazy|int $commentCount24h,
        public Lazy|int $oldestComment24hId,
        public Lazy|int $commentCount7d,
        public Lazy|int $oldestComment7dId,
    ) {
    }

    public static function fromRecentlyActiveTopic(ForumTopic $topic): self
    {
        return new self(
            id: $topic->id,
            title: $topic->title,
            createdAt: $topic->DateCreated,

            user: null, // TODO

            commentCount24h: Lazy::create(fn () => $topic->comment_count_24h),
            oldestComment24hId: Lazy::create(fn () => $topic->oldest_comment_id_24h),
            commentCount7d: Lazy::create(fn () => $topic->comment_count_7d),
            oldestComment7dId: Lazy::create(fn () => $topic->oldest_comment_id_7d),

            latestComment: Lazy::when(
                fn () => $topic->latestComment !== null,
                fn () => new ForumTopicCommentData(
                    id: $topic->latestComment->id,
                    body: Shortcode::stripAndClamp($topic->latestComment->body, 200),
                    createdAt: $topic->latestComment->DateCreated,
                    updatedAt: $topic->latestComment->DateModified,
                    user: UserData::from($topic->latestComment->user),
                    authorized: $topic->latestComment->Authorised,
                )
            ),
        );
    }
}
