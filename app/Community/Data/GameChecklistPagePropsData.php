<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameChecklistPageProps')]
class GameChecklistPagePropsData extends Data
{
    public function __construct(
        public UserData $player,

        /** @var GameGroupData[] */
        public array $groups,
    ) {
    }
}
