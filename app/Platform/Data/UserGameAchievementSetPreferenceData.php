<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\UserGameAchievementSetPreference;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserGameAchievementSetPreference')]
class UserGameAchievementSetPreferenceData extends Data
{
    public function __construct(
        public int $gameAchievementSetId,
        public bool $optedIn,
    ) {
    }

    public static function fromUserGameAchievementSetPreference(
        UserGameAchievementSetPreference $preference,
    ): self {
        return new self(
            gameAchievementSetId: $preference->game_achievement_set_id,
            optedIn: $preference->opted_in,
        );
    }
}
