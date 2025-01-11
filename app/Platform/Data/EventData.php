<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Event;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Event')]
class EventData extends Data
{
    public function __construct(
        public int $id,
        public Lazy|GameData $legacyGame,
    ) {
    }

    public static function fromEvent(Event $event): self
    {
        return new self(
            id: $event->id,
            legacyGame: Lazy::create(fn () => GameData::fromGame($event->game)),
        );
    }
}
