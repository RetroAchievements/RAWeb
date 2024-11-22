<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use App\Platform\Data\SystemData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserGameListPageProps<TItems = App.Platform.Data.GameListEntry>')]
class UserGameListPagePropsData extends Data
{
    /**
     * @param SystemData[] $filterableSystemOptions
     */
    public function __construct(
        public PaginatedData $paginatedGameListEntries,
        public array $filterableSystemOptions,
        public UserPermissionsData $can,
    ) {
    }
}
