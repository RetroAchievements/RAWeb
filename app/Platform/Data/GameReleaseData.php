<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

#[TypeScript('GameRelease')]
class GameReleaseData extends Data
{
    public function __construct(
        public int $id,
        public Carbon|null $releasedAt,
        public ReleasedAtGranularity|null $releasedAtGranularity,
        public string $title,
        public GameReleaseRegion|null $region,
        public bool $isCanonicalGameTitle,
    ) {
    }
}
