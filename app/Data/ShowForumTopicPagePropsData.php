<?php

declare(strict_types=1);

namespace App\Data;

use App\Community\Data\ShortcodeDynamicEntitiesData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ShowForumTopicPageProps<TItems = App.Data.ForumTopicComment>')]
class ShowForumTopicPagePropsData extends Data
{
    /**
     * @param Collection<int, UserData>|null $accessibleTeamAccounts
     */
    public function __construct(
        public UserPermissionsData $can,
        public ShortcodeDynamicEntitiesData $dynamicEntities,
        public ForumTopicData $forumTopic,
        public bool $isSubscribed,
        public PaginatedData $paginatedForumTopicComments,
        public string $metaDescription,
        public ?Collection $accessibleTeamAccounts = null,
    ) {
    }
}
