<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Achievement')]
class AchievementData extends Data
{
    public function __construct(
        public string $badgeLockedUrl,
        public string $badgeUnlockedUrl,
        public int $id,
        public string $title,

        public Lazy|Carbon $createdAt,
        public Lazy|string $description,
        public Lazy|string|null $decorator,
        public Lazy|UserData $developer,
        public Lazy|AchievementFlag $flags,
        public Lazy|GameData $game,
        public Lazy|int $orderColumn,
        public Lazy|int $points,
        public Lazy|int $pointsWeighted,
        #[LiteralTypeScriptType("'progression' | 'win_condition' | 'missable' | null")]
        public Lazy|string $type,
        public Lazy|string $unlockedAt,
        public Lazy|string $unlockedHardcoreAt,
        #[LiteralTypeScriptType('string')]
        public Lazy|float $unlockHardcorePercentage,
        #[LiteralTypeScriptType('string')]
        public Lazy|float $unlockPercentage,
        public Lazy|int $unlocksHardcoreTotal,
        public Lazy|int $unlocksTotal,
    ) {
    }

    public static function fromAchievement(
        Achievement $achievement,
        ?PlayerAchievement $playerAchievement = null,
    ): self {
        return new self(
            badgeLockedUrl: $achievement->badge_locked_url,
            badgeUnlockedUrl: $achievement->badge_unlocked_url,
            id: $achievement->id,
            title: $achievement->title,

            createdAt: Lazy::create(fn () => $achievement->DateCreated),
            description: Lazy::create(fn () => $achievement->description),
            decorator: Lazy::create(fn () => null),
            developer: Lazy::create(fn () => UserData::from($achievement->developer)),
            flags: Lazy::create(fn () => AchievementFlag::from($achievement->Flags)),
            game: Lazy::create(fn () => GameData::fromGame($achievement->game)),
            orderColumn: Lazy::create(fn () => $achievement->DisplayOrder),
            points: Lazy::create(fn () => $achievement->points),
            pointsWeighted: Lazy::create(fn () => $achievement->points_weighted),
            type: Lazy::create(fn () => $achievement->type),
            unlockedAt: Lazy::create(fn () => $playerAchievement?->unlocked_at ?? $achievement->player_unlocked_at ?? null),
            unlockedHardcoreAt: Lazy::create(fn () => $playerAchievement?->unlocked_hardcore_at ?? $achievement->player_unlocked_hardcore_at ?? null),
            unlockHardcorePercentage: Lazy::create(fn () => $achievement->unlock_hardcore_percentage),
            unlockPercentage: Lazy::create(fn () => $achievement->unlock_percentage),
            unlocksHardcoreTotal: Lazy::create(fn () => $achievement->unlocks_hardcore_total),
            unlocksTotal: Lazy::create(fn () => $achievement->unlocks_total),
        );
    }
}
