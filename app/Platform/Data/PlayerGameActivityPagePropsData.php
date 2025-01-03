<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameActivityPageProps')]
class PlayerGameActivityPagePropsData extends Data
{
    public function __construct(
        public UserData $player,
        public GameData $game,
        public ?PlayerGameData $playerGame,
        public PlayerGameActivityData $activity,
    ) {
    }
}
