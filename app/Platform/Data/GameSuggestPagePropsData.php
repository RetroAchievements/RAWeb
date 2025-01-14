<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSuggestPageProps<TItems = App.Platform.Data.GameSuggestionEntry>')]
class GameSuggestPagePropsData extends Data
{
    public function __construct(
        public PaginatedData $paginatedGameListEntries,
        public ?GameData $sourceGame = null,
        public int $defaultDesktopPageSize = 10,
    ) {
    }
}
