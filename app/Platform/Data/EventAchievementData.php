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
        public Lazy|Carbon $activeUntil,
    ) {
    }

    public static function fromEventAchievement(
        EventAchievement $eventAchievement,
    ): self {
        $playerAchievement = $eventAchievement->achievement->relationLoaded('playerAchievements')
            ? $eventAchievement->achievement->playerAchievements->first()
            : null;

        return new self(
            achievement: Lazy::create(fn () => AchievementData::fromAchievement(
                $eventAchievement->achievement,
                $playerAchievement
            )),
            sourceAchievement: Lazy::create(fn () => $eventAchievement->sourceAchievement
                ? AchievementData::fromAchievement($eventAchievement->sourceAchievement)
                : null
            ),
            event: Lazy::create(fn () => EventData::fromEvent($eventAchievement->event)),
            activeFrom: Lazy::create(fn () => $eventAchievement->active_from),
            activeUntil: Lazy::create(fn () => $eventAchievement->active_until)
        );
    }
}
