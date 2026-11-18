<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

/**
 * The body of the achievement distribution response.
 *
 * The endpoint sends a summary of many players, not a resource, so the payload
 * goes in `meta` and not in `data`.
 */
class GameAchievementDistributionData extends Data
{
    public function __construct(
        public SelfLinkData $links,
        public GameAchievementDistributionMetaData $meta,
    ) {
    }
}
