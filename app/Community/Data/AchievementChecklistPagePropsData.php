<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementChecklistPageProps')]
class AchievementChecklistPagePropsData extends Data
{
    public function __construct(
        public UserData $player,

        /** @var AchievementGroupData[] */
        public array $groups,
    ) {
    }
}
