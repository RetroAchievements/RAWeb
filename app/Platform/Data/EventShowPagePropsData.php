<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EventShowPagePropsData')]
class EventShowPagePropsData extends Data
{
    public function __construct(
        public EventData $event,
        public UserPermissionsData $can,
        /** @var GameSetData[] */
        public array $hubs,
        public ?PlayerGameData $playerGame,
        public ?PlayerGameProgressionAwardsData $playerGameProgressionAwards,
    ) {
    }
}
