<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameProgressionAwards')]
class PlayerGameProgressionAwardsData extends Data
{
    /**
     * Represents all a player's awards for a specific game.
     */
    public function __construct(
        public ?PlayerBadgeData $beatenSoftcore,
        public ?PlayerBadgeData $beatenHardcore,
        public ?PlayerBadgeData $completed,
        public ?PlayerBadgeData $mastered,
    ) {
    }

    /**
     * Creates a new PlayerGameProgressionAwardsData instance from a keyed array of PlayerBadge models.
     */
    public static function fromArray(array $awards): self
    {
        return new self(
            beatenSoftcore: $awards['beaten-softcore'] ? PlayerBadgeData::fromPlayerBadge($awards['beaten-softcore']) : null,
            beatenHardcore: $awards['beaten-hardcore'] ? PlayerBadgeData::fromPlayerBadge($awards['beaten-hardcore']) : null,
            completed: $awards['completed'] ? PlayerBadgeData::fromPlayerBadge($awards['completed']) : null,
            mastered: $awards['mastered'] ? PlayerBadgeData::fromPlayerBadge($awards['mastered']) : null,
        );
    }
}
