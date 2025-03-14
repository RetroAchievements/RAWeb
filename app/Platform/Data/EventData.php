<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Event;
use App\Platform\Enums\EventState;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Event')]
class EventData extends Data
{
    public function __construct(
        public int $id,
        public ?Carbon $activeFrom,
        public ?Carbon $activeThrough,
        public Lazy|GameData $legacyGame,
        /** @var EventAchievementData[] */
        public Lazy|array $eventAchievements,
        /** @var EventAwardData[] */
        public Lazy|array $eventAwards,
        public Lazy|EventState $state,
    ) {
    }

    public static function fromEvent(Event $event): self
    {
        return new self(
            id: $event->id,
            activeFrom: $event->active_from,
            activeThrough: $event->active_through,
            legacyGame: Lazy::create(fn () => GameData::fromGame($event->legacyGame)),

            eventAchievements: Lazy::create(fn () => $event->achievements->map(
                fn ($eventAchievement) => EventAchievementData::fromEventAchievement(
                    $eventAchievement
                )->include(
                    'achievement',
                    'sourceAchievement',
                    'activeFrom',
                    'activeThrough',
                )
            )->all()),

            eventAwards: Lazy::create(fn () => $event->awards->map(
                fn ($eventAward) => EventAwardData::fromEventAward($eventAward)
            )->all()),

            state: Lazy::create(fn () => $event->state),
        );
    }
}
