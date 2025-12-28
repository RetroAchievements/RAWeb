<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\EventAchievement;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EventAchievement')]
class EventAchievementData extends Data
{
    public function __construct(
        public Lazy|AchievementData $achievement,
        public Lazy|AchievementData|null $sourceAchievement,
        public Lazy|EventData $event,
        public Lazy|Carbon $activeFrom,
        public Lazy|Carbon $activeThrough,
        public Lazy|Carbon $activeUntil,
        public bool $isObfuscated = false,
    ) {
    }

    public static function fromEventAchievement(
        EventAchievement $eventAchievement,
    ): self {
        $playerAchievement = $eventAchievement->achievement->relationLoaded('playerAchievements')
            ? $eventAchievement->achievement->playerAchievements->first()
            : null;

        $isObfuscated =
            $eventAchievement->active_from !== null
            && $eventAchievement->active_from->isFuture()
            && $eventAchievement->sourceAchievement !== null;

        if ($isObfuscated) {
            // For upcoming achievements with a sourceAchievement set, return a special DTO with scrubbed data.
            // We can't just rely on hiding them in the UI, because they're still injected into page props.
            return new self(
                achievement: Lazy::create(fn () => new AchievementData(
                    badgeLockedUrl: media_asset('Badge/00000_lock.png'),
                    badgeUnlockedUrl: media_asset('Badge/00000.png'),
                    createdAt: Lazy::create(fn () => $eventAchievement->achievement->created_at),
                    decorator: Lazy::create(fn () => $eventAchievement->decorator),
                    description: Lazy::create(fn () => '?????'),
                    developer: Lazy::create(fn () => null),
                    game: Lazy::create(fn () => GameData::fromGame($eventAchievement->achievement->game)),
                    groupId: Lazy::create(fn () => null),
                    id: $eventAchievement->achievement->id,
                    isPromoted: Lazy::create(fn () => $eventAchievement->achievement->is_promoted),
                    orderColumn: Lazy::create(fn () => $eventAchievement->achievement->order_column),
                    points: Lazy::create(fn () => $eventAchievement->achievement->points),
                    pointsWeighted: Lazy::create(fn () => $eventAchievement->achievement->points_weighted),
                    title: 'Upcoming Challenge',
                    type: Lazy::create(fn () => null),
                    unlockedAt: Lazy::create(fn () => $playerAchievement?->unlocked_at),
                    unlockedHardcoreAt: Lazy::create(fn () => $playerAchievement?->unlocked_hardcore_at),
                    unlockHardcorePercentage: Lazy::create(fn () => $eventAchievement->achievement->unlock_hardcore_percentage),
                    unlockPercentage: Lazy::create(fn () => $eventAchievement->achievement->unlock_percentage),
                    unlocksHardcore: Lazy::create(fn () => $eventAchievement->achievement->unlocks_hardcore),
                    unlocksTotal: Lazy::create(fn () => $eventAchievement->achievement->unlocks_total),
                )),
                sourceAchievement: Lazy::create(fn () => null), // Force sourceAchievement to be null
                event: Lazy::create(fn () => EventData::fromEvent($eventAchievement->event)),
                activeFrom: Lazy::create(fn () => $eventAchievement->active_from),
                activeThrough: Lazy::create(fn () => $eventAchievement->active_through),
                activeUntil: Lazy::create(fn () => $eventAchievement->active_until),
                isObfuscated: true,
            );
        }

        return new self(
            achievement: Lazy::create(function () use ($eventAchievement, $playerAchievement) {
                $achievement = AchievementData::fromAchievement(
                    $eventAchievement->achievement,
                    $playerAchievement
                );
                $achievement->decorator = $eventAchievement->decorator;

                return $achievement;
            }),
            sourceAchievement: Lazy::create(fn () => $eventAchievement->sourceAchievement
                ? AchievementData::fromAchievement($eventAchievement->sourceAchievement)
                : null
            ),
            event: Lazy::create(fn () => EventData::fromEvent($eventAchievement->event)),
            activeFrom: Lazy::create(fn () => $eventAchievement->active_from),
            activeThrough: Lazy::create(fn () => $eventAchievement->active_through),
            activeUntil: Lazy::create(fn () => $eventAchievement->active_until),
            isObfuscated: false,
        );
    }
}
