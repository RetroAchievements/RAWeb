<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EditForumTopicCommentPageProps')]
class EditForumTopicCommentPagePropsData extends Data
{
    public function __construct(
        public ForumTopicCommentData $forumTopicComment,
    ) {
    }
}
