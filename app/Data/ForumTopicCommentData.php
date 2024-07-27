<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ForumTopicComment')]
class ForumTopicCommentData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public Carbon $createdAt,
        public Carbon $updatedAt,
        public UserData $user,
        public bool $authorized, // TODO migrate to $authorizedAt
        public ?int $forumTopicId = null,
    ) {
    }
}
