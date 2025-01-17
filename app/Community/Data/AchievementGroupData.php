<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Platform\Data\AchievementData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementGroup')]
class AchievementGroupData extends Data
{
    public function __construct(
        public string $header,

        /** @var AchievementData[] */
        public array $achievements,
    ) {
    }
}
