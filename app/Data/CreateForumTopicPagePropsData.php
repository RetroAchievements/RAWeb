<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('CreateForumTopicPageProps')]
class CreateForumTopicPagePropsData extends Data
{
    /**
     * @param Collection<int, UserData>|null $accessibleTeamAccounts
     */
    public function __construct(
        public ForumData $forum,
        public ?Collection $accessibleTeamAccounts = null,
    ) {
    }
}
