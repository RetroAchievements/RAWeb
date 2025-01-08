<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('RecentUnlock')]
class RecentUnlockData extends Data
{
    public function __construct(
        public AchievementData $achievement,
        public GameData $game,
        public UserData $user,
        public Carbon $unlockedAt,
        public bool $isHardcore,
    ) {
    }
}
