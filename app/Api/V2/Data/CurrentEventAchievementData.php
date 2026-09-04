<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

/**
 * Identifies an event achievement that is active now.
 *
 * A client uses these identifiers to fetch the achievement itself. It does not
 * need to know which achievement is current.
 */
class CurrentEventAchievementData extends Data
{
    public function __construct(
        public string $id,
        public string $achievementId,

        /**
         * The achievement to show. Players unlock this one.
         */
        public string $sourceAchievementId,

        public string $eventId,
        public string $activeFrom,
        public string $activeUntil,
        public string $activeThrough,

        public ?string $decorator,
    ) {
    }
}
