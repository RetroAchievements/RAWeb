<?php

declare(strict_types=1);

namespace App\Data;

use App\Platform\Data\GameData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('StaticGameAward')]
class StaticGameAwardData extends Data
{
    public function __construct(
        public GameData $game,
        public UserData $user,
        public Carbon $awardedAt,
    ) {
    }
}
