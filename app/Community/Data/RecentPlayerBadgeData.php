<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Platform\Data\GameData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('RecentPlayerBadge')]
class RecentPlayerBadgeData extends Data
{
    public function __construct(
        public GameData $game,
        public string $awardType,
        public UserData $user,
        public Carbon $earnedAt,
    ) {
    }
}
