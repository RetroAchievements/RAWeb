<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Enums\ReleasedAtGranularity;
use Carbon\Carbon;

class FormatGameReleaseDateAction
{
    public function execute(?Carbon $releasedAt, ?ReleasedAtGranularity $releasedAtGranularity): ?string
    {
        if (!$releasedAt) {
            return null;
        }

        return match ($releasedAtGranularity) {
            ReleasedAtGranularity::Year => $releasedAt->format('Y'),
            ReleasedAtGranularity::Month => $releasedAt->format('F Y'),
            default => $releasedAt->format('F j, Y'),
        };
    }
}
