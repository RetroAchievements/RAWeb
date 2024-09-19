<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Achievement')]
class AchievementData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public Lazy|string $badgeUnlockedUrl,
        public Lazy|string $badgeLockedUrl,
        public Lazy|GameData $game,
        public Lazy|string $unlockedAt,
        public Lazy|string $unlockedHardcoreAt,
    ) {
    }

    public static function fromAchievement(
        Achievement $achievement,
        ?PlayerAchievement $playerAchievement = null,
    ): self {
        return new self(
            id: $achievement->id,
            title: $achievement->title,
            badgeUnlockedUrl: Lazy::create(fn () => $achievement->badge_unlocked_url),
            badgeLockedUrl: Lazy::create(fn () => $achievement->badge_locked_url),
            game: Lazy::create(fn () => GameData::fromGame($achievement->game)),

            unlockedAt: Lazy::create(fn () => $playerAchievement?->unlocked_at),
            unlockedHardcoreAt: Lazy::create(fn () => $playerAchievement?->unlocked_hardcore_at),
        );
    }
}
