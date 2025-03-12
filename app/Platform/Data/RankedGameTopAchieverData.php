<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('RankedGameTopAchiever')]
class RankedGameTopAchieverData extends Data
{
    public function __construct(
        public int $rank,
        public UserData $user,
        public int $score,
        public ?PlayerBadgeData $badge,
    ) {
    }
}
