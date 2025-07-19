<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameRelease;
use App\Platform\Enums\ReleasedAtGranularity;
use Carbon\Carbon;

class ProcessGameReleasesForViewAction
{
    /**
     * Process game releases to deduplicate by region and sort by date.
     * This ensures the most specific date is shown when multiple releases exist for the same region.
     */
    public function execute(Game $game): array
    {
        $releases = $game->releases;

        // First, deduplicate by region, keeping only the earliest release per region.
        $regionMap = [];

        foreach ($releases as $release) {
            // Normalize the region for comparison.
            $normalizedRegion = !$release->region || $release->region->value === 'other' || $release->region->value === 'worldwide'
                ? 'worldwide'
                : $release->region->value;

            $existing = $regionMap[$normalizedRegion] ?? null;

            // If there's no existing release for this region, or this release is earlier, use it.
            if (
                !$existing
                || ($release->released_at && (!$existing->released_at || $this->isEarlierRelease($release, $existing)))
            ) {
                $regionMap[$normalizedRegion] = $release;
            }
        }

        $dedupedReleases = array_values($regionMap);

        // Sort by date.
        usort($dedupedReleases, function ($a, $b) {
            if (!$a->released_at && !$b->released_at) {
                return 0;
            }
            if (!$a->released_at) {
                return 1;
            }
            if (!$b->released_at) {
                return -1;
            }

            // First, normalize dates based on granularity.
            $normalizedDateA = $this->normalizeDate($a->released_at, $a->released_at_granularity);
            $normalizedDateB = $this->normalizeDate($b->released_at, $b->released_at_granularity);

            // Then, compare normalized dates.
            $dateDiff = $normalizedDateA->timestamp - $normalizedDateB->timestamp;

            if ($dateDiff !== 0) {
                return $dateDiff;
            }

            // If the dates are equal, sort by granularity (more specific dates first).
            $granularityOrder = ['day' => 3, 'month' => 2, 'year' => 1];
            $granularityA = $a->released_at_granularity ? $granularityOrder[$a->released_at_granularity->value] : 0;
            $granularityB = $b->released_at_granularity ? $granularityOrder[$b->released_at_granularity->value] : 0;

            return $granularityB - $granularityA;
        });

        return $dedupedReleases;
    }

    private function isEarlierRelease(GameRelease $a, GameRelease $b): bool
    {
        $normalizedDateA = $this->normalizeDate($a->released_at, $a->released_at_granularity);
        $normalizedDateB = $this->normalizeDate($b->released_at, $b->released_at_granularity);

        return $normalizedDateA->lt($normalizedDateB);
    }

    /**
     * Normalize dates based on granularity to ensure proper comparison.
     * Year-only dates are normalized to end of year, month dates to end of month.
     * This ensures more specific dates are prioritized over less specific ones.
     */
    private function normalizeDate(Carbon $date, ?ReleasedAtGranularity $granularity): Carbon
    {
        switch ($granularity?->value) {
            case 'year':
                return $date->endOfYear();

            case 'month':
                return $date->endOfMonth();

            case 'day':
            default:
                return $date->startOfDay();
        }
    }
}
