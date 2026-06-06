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
            return new self(
                achievement: Lazy::create(fn () => AchievementData::fromObfuscated(
                    $eventAchievement->achievement,
                    $playerAchievement,
                )),
                sourceAchievement: Lazy::create(fn () => null),
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
