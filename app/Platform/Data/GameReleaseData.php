<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameRelease')]
class GameReleaseData extends Data
{
    public function __construct(
        public int $id,
        public ?Carbon $releasedAt,
        public ?ReleasedAtGranularity $releasedAtGranularity,
        public string $title,
        public ?GameReleaseRegion $region,
        public bool $isCanonicalGameTitle,
    ) {
    }
}
