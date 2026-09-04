<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\EventAchievement;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ActiveEventAchievement')]
class ActiveEventAchievementData extends Data
{
    public function __construct(
        public int $achievementId,
        public int $eventId,
        public string $eventTitle,
        public ?Carbon $activeUntil,
        public bool $userUnlocked,
    ) {
    }

    public static function fromEventAchievement(
        EventAchievement $eventAchievement,
        bool $userUnlocked,
    ): self {
        return new self(
            achievementId: $eventAchievement->source_achievement_id,
            eventId: $eventAchievement->event->id,
            eventTitle: $eventAchievement->event->title,
            activeUntil: $eventAchievement->active_until,
            userUnlocked: $userUnlocked,
        );
    }
}
