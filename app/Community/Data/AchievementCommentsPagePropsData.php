<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Platform\Data\AchievementData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementCommentsPageProps<TItems = App.Community.Data.Comment>')]
class AchievementCommentsPagePropsData extends Data
{
    public function __construct(
        public AchievementData $achievement,
        public PaginatedData $paginatedComments,
        public bool $isSubscribed,
        public bool $canComment,
    ) {
    }
}
