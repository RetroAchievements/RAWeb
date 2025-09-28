<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Platform\Data\GameData;
use Illuminate\Support\Collection;
use Inertia\DeferProp;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSetRequestsPagePropsData')]
class GameSetRequestsPagePropsData extends Data
{
    /**
     * @param Collection<int, UserData> $initialRequestors
     * @param DeferProp|Collection<int, UserData> $deferredRequestors
     */
    public function __construct(
        public GameData $game,
        public Collection $initialRequestors,
        public DeferProp|Collection $deferredRequestors,
        public int $totalCount,
    ) {
    }
}
