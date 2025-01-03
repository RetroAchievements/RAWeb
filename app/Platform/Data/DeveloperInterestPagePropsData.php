<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('DeveloperInterestPageProps')]
class DeveloperInterestPagePropsData extends Data
{
    public function __construct(
        public GameData $game,
        /** @var Collection<int, UserData> */
        public Collection $developers,
    ) {
    }
}
