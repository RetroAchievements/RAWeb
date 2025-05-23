<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Enums\ReleasedAtGranularity;

class SyncGameReleaseDatesAction
{
    public function execute(Game $game): void
    {
        // Get all releases with dates and find the truly earliest one.
        $releasesWithDates = $game->releases()
            ->whereNotNull('released_at')
            ->get();

        $currentReleasedAt = $game->released_at;
        $currentReleasedAtGranularity = $game->released_at_granularity;

        if ($releasesWithDates->isEmpty()) {
            // There aren't any releases with dates. Clear the game's release date.
            $game->released_at = null;
            $game->released_at_granularity = null;
        } else {
            // Taking granularity into account, find the earliest release date.
            $earliestRelease = $releasesWithDates->sortBy(function ($release) {
                $date = $release->released_at;

                // Normalize dates based on granularity to find the true earliest.
                switch ($release->released_at_granularity) {
                    case ReleasedAtGranularity::Year:
                        // Year granularity means beginning of that year.
                        return $date->startOfYear();

                    case ReleasedAtGranularity::Month:
                        // Month granularity means beginning of that month.
                        return $date->startOfMonth();

                    case ReleasedAtGranularity::Day:
                    default:
                        // Day granularity uses the exact date.
                        return $date->startOfDay();
                }
            })->first();

            // Set the game's release date to the earliest release we could find.
            $game->released_at = $earliestRelease->released_at;
            $game->released_at_granularity = $earliestRelease->released_at_granularity;
        }

        // Only save if values actually changed.
        if (
            $currentReleasedAt != $game->released_at
            || $currentReleasedAtGranularity != $game->released_at_granularity
        ) {
            $game->saveQuietly();
        }
    }
}
