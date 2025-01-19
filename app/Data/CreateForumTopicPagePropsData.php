<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('CreateForumTopicPageProps')]
class CreateForumTopicPagePropsData extends Data
{
    public function __construct(
        public ForumData $forum,
    ) {
    }
}
