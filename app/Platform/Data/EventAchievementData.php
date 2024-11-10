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
        public Lazy|AchievementData $sourceAchievement,
        public Lazy|Carbon $activeUntil,
        public Lazy|int $forumTopicId,
    ) {
    }

    public static function fromEventAchievement(
        EventAchievement $achievement,
    ): self {
        return new self(
            achievement: Lazy::create(fn () => AchievementData::fromAchievement($achievement->achievement)),
            sourceAchievement: Lazy::create(fn () => AchievementData::fromAchievement($achievement->sourceAchievement)),
            activeUntil: Lazy::create(fn () => $achievement->active_until),
            forumTopicId: Lazy::create(fn () => $achievement->achievement->game->ForumTopicID),
        );
    }
}
