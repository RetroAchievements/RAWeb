<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementRecentUnlock')]
class AchievementRecentUnlockData extends Data
{
    public function __construct(
        public UserData $user,
        public Carbon $unlockedAt,
        public bool $isHardcore,
    ) {
    }
}
