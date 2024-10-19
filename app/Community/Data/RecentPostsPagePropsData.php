<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('RecentPostsPageProps<TItems = App.Data.ForumTopic>')]
class RecentPostsPagePropsData extends Data
{
    public function __construct(
        public PaginatedData $paginatedTopics,
    ) {
    }
}
