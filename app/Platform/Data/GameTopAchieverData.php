<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameTopAchiever')]
class GameTopAchieverData extends Data
{
    public function __construct(
        public string $userDisplayName,
        public string $userAvatarUrl,
        public int $achievementsUnlockedHardcore,
        public int $pointsHardcore,
        public Carbon $lastUnlockHardcoreAt,
        public ?Carbon $beatenHardcoreAt,
    ) {
    }

    public static function fromTopAchiever(array $topAchiever): self
    {
        return new self(
            userDisplayName: $topAchiever['user_display_name'],
            userAvatarUrl: $topAchiever['user_avatar_url'],
            achievementsUnlockedHardcore: $topAchiever['achievements_unlocked_hardcore'],
            pointsHardcore: $topAchiever['points_hardcore'],
            lastUnlockHardcoreAt: Carbon::parse($topAchiever['last_unlock_hardcore_at']),
            beatenHardcoreAt: $topAchiever['beaten_hardcore_at']
                ? Carbon::parse($topAchiever['beaten_hardcore_at'])
                : null,
        );
    }
}
