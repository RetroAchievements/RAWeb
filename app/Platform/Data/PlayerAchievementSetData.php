<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\PlayerAchievementSet;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerAchievementSet')]
class PlayerAchievementSetData extends Data
{
    public function __construct(
        // Model fields
        public ?Carbon $completedAt,
        public ?Carbon $completedHardcoreAt,

        // Lazy fields
        public Lazy|int|null $timeTaken,
        public Lazy|int|null $timeTakenHardcore,
    ) {
    }

    public static function fromPlayerAchievementSet(PlayerAchievementSet $playerAchievementSet): self
    {
        return new self(
            completedAt: $playerAchievementSet->completed_at,
            completedHardcoreAt: $playerAchievementSet->completed_hardcore_at,

            timeTaken: Lazy::create(fn () => $playerAchievementSet->time_taken),
            timeTakenHardcore: Lazy::create(fn () => $playerAchievementSet->time_taken_hardcore),
        );
    }
}
