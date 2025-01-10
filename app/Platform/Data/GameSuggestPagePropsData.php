<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSuggestPageProps<TItems = App.Platform.Data.GameSuggestionEntry>')]
class GameSuggestPagePropsData extends Data
{
    public function __construct(
        public PaginatedData $paginatedGameListEntries,
        public string $persistenceCookieName,
        #[LiteralTypeScriptType('Record<string, any> | null')]
        public ?array $persistedViewPreferences = null,
        public int $defaultDesktopPageSize = 10,
    ) {
    }
}
