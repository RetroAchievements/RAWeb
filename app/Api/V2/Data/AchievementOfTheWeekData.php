<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

/**
 * The body of the Achievement of the Week response.
 *
 * The endpoint resolves an identifier. It does not send the achievement itself,
 * so the payload goes in `meta` and not in `data`.
 */
class AchievementOfTheWeekData extends Data
{
    public function __construct(
        public SelfLinkData $links,
        public AchievementOfTheWeekMetaData $meta,
    ) {
    }
}
