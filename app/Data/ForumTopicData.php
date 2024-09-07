<?php

declare(strict_types=1);

namespace App\Data;

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
        public Lazy|ForumTopicCommentData $latestComment,
        public Lazy|int $commentCount24h,
        public Lazy|int $oldestComment24hId,
        public Lazy|int $commentCount7d,
        public Lazy|int $oldestComment7dId,
        public ?UserData $user = null,
        ) {
    }

    public static function fromRecentlyActiveTopic(array $topic): self
    {
        return new self(
            id: $topic['ForumTopicID'],
            title: $topic['ForumTopicTitle'],
            createdAt: Carbon::parse($topic['PostedAt']),

            user: null,

            commentCount24h: Lazy::create(fn () => $topic['Count_1d']),
            oldestComment24hId: Lazy::create(fn () => $topic['CommentID_1d']),
            commentCount7d: Lazy::create(fn () => $topic['Count_7d']),
            oldestComment7dId: Lazy::create(fn () => $topic['CommentID_7d']),

            latestComment: Lazy::when(
                fn () => isset($topic['CommentID']),
                fn () => new ForumTopicCommentData(
                    id: $topic['CommentID'],
                    body: Shortcode::stripAndClamp($topic['ShortMsg'], 200),
                    createdAt: Carbon::parse($topic['PostedAt']),
                    updatedAt: null, // If no updated date is available, you can set it to null or handle accordingly
                    user: UserData::fromRecentForumTopic($topic),
                    authorized: true // Assuming it's authorized
                )
            ),

        );
    }
}
