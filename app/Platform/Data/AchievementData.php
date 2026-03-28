<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
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
        public Lazy|string|null $embedUrl,
        public Lazy|bool $isPromoted,
        public Lazy|GameData $game,
        public Lazy|int|null $groupId,
        public Lazy|bool $hasVisibleUserComments,
        public Lazy|int $numUnresolvedTickets,
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
        public Lazy|int $unlocksHardcore,
        public Lazy|int $unlocksTotal,

        public Lazy|UserData|null $activeMaintainer = null,
        public Lazy|Carbon|null $modifiedAt = null,
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

            createdAt: Lazy::create(fn () => $achievement->created_at),
            description: Lazy::create(fn () => $achievement->description),
            decorator: Lazy::create(fn () => null),
            developer: Lazy::create(fn () => UserData::fromUser($achievement->developer)),
            embedUrl: Lazy::create(fn () => $achievement->embed_url),
            isPromoted: Lazy::create(fn () => $achievement->is_promoted),
            game: Lazy::create(fn () => GameData::fromGame($achievement->game)),
            groupId: Lazy::create(fn () => $achievement->pivot?->achievement_group_id),
            hasVisibleUserComments: Lazy::create(
                fn () => (bool) $achievement->getAttribute('has_visible_user_comments')
            ),
            numUnresolvedTickets: Lazy::create(fn () => $achievement->tickets()->unresolved()->count()),
            orderColumn: Lazy::create(fn () => $achievement->order_column),
            points: Lazy::create(fn () => $achievement->points),
            pointsWeighted: Lazy::create(fn () => $achievement->points_weighted),
            type: Lazy::create(fn () => $achievement->type),
            unlockedAt: Lazy::create(fn () => $playerAchievement?->unlocked_at ?? $achievement->player_unlocked_at ?? null),
            unlockedHardcoreAt: Lazy::create(fn () => $playerAchievement?->unlocked_hardcore_at ?? $achievement->player_unlocked_hardcore_at ?? null),
            unlockHardcorePercentage: Lazy::create(fn () => $achievement->unlock_hardcore_percentage),
            unlockPercentage: Lazy::create(fn () => $achievement->unlock_percentage),
            unlocksHardcore: Lazy::create(fn () => $achievement->unlocks_hardcore),
            unlocksTotal: Lazy::create(fn () => $achievement->unlocks_total),

            activeMaintainer: Lazy::create(fn () => $achievement->activeMaintainer?->user
                ? UserData::fromUser($achievement->activeMaintainer->user)
                : null
            ),
            modifiedAt: Lazy::create(fn () => $achievement->modified_at),
        );
    }

    /**
     * Build a scrubbed DTO that hides the real details of an upcoming event achievement.
     * We can't just hide this in the UI - it leaks into page props.
     */
    public static function fromObfuscated(
        Achievement $achievement,
        ?PlayerAchievement $playerAchievement = null,
    ): self {
        return new self(
            badgeLockedUrl: media_asset('Badge/00000_lock.png'),
            badgeUnlockedUrl: media_asset('Badge/00000.png'),
            id: $achievement->id,
            title: 'Upcoming Challenge',

            createdAt: Lazy::create(fn () => $achievement->created_at),
            description: Lazy::create(fn () => '?????'),
            decorator: Lazy::create(fn () => null),
            developer: Lazy::create(fn () => null),
            embedUrl: Lazy::create(fn () => null),
            isPromoted: Lazy::create(fn () => $achievement->is_promoted),
            game: Lazy::create(fn () => GameData::fromGame($achievement->game)),
            groupId: Lazy::create(fn () => null),
            hasVisibleUserComments: Lazy::create(fn () => false),
            numUnresolvedTickets: Lazy::create(fn () => 0),
            orderColumn: Lazy::create(fn () => $achievement->order_column),
            points: Lazy::create(fn () => $achievement->points),
            pointsWeighted: Lazy::create(fn () => $achievement->points_weighted),
            type: Lazy::create(fn () => null),
            unlockedAt: Lazy::create(fn () => $playerAchievement?->unlocked_at),
            unlockedHardcoreAt: Lazy::create(fn () => $playerAchievement?->unlocked_hardcore_at),
            unlockHardcorePercentage: Lazy::create(fn () => $achievement->unlock_hardcore_percentage),
            unlockPercentage: Lazy::create(fn () => $achievement->unlock_percentage),
            unlocksHardcore: Lazy::create(fn () => $achievement->unlocks_hardcore),
            unlocksTotal: Lazy::create(fn () => $achievement->unlocks_total),
        );
    }
}
