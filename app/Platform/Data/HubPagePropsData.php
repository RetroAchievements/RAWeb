<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('HubPageProps<TItems = App.Platform.Data.GameListEntry>')]
class HubPagePropsData extends Data
{
    /**
     * @param SystemData[] $filterableSystemOptions
     * @param GameSetData[] $breadcrumbs Ordered array of hubs from root to current
     * @param GameSetData[] $relatedHubs All hubs that are attached to this hub
     */
    public function __construct(
        public GameSetData $hub,
        public PaginatedData $paginatedGameListEntries,
        public array $filterableSystemOptions,
        public UserPermissionsData $can,
        public array $breadcrumbs,
        public array $relatedHubs,
        public string $persistenceCookieName,
        #[LiteralTypeScriptType('Record<string, any> | null')]
        public ?array $persistedViewPreferences = null,
        public int $defaultDesktopPageSize = 25,
    ) {
    }
}
