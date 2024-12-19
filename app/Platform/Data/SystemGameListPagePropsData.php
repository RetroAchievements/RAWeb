<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('SystemGameListPageProps<TItems = App.Platform.Data.GameListEntry>')]
class SystemGameListPagePropsData extends Data
{
    public function __construct(
        public SystemData $system,
        public PaginatedData $paginatedGameListEntries,
        public UserPermissionsData $can,
        public string $persistenceCookieName,
        #[LiteralTypeScriptType('Record<string, any> | null')]
        public ?array $persistedViewPreferences = null,
        public int $defaultDesktopPageSize = 100,
    ) {
    }
}
