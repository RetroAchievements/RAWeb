<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameTopAchieversPageProps<TItems = App.Platform.Data.GameTopAchiever>')]
class GameTopAchieversPagePropsData extends Data
{
    public function __construct(
        public GameData $game,
        public PaginatedData $paginatedUsers,
    ) {
    }
}
