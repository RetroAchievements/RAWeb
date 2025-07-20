<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameRelease;
use App\Platform\Enums\ReleasedAtGranularity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProcessGameReleasesForViewAction
{
    /**
     * Process game releases to deduplicate by region and sort by date.
     * This ensures the most specific date is shown when multiple releases exist for the same region.
     */
    public function execute(Game $game): array
    {
        $releases = $game->releases;

        $regionDeduplicated = $this->deduplicateByRegion($releases);

        // We need to preserve releases with unique titles even if they were filtered out
        // during region deduplication. This ensures alternative game names appear in
        // the "Other Names" UI component.
        $withUniqueTitles = $this->preserveUniqueTitles($regionDeduplicated, $releases);

        return $this->sortReleases($withUniqueTitles);
    }

    /**
     * @param Collection<int, GameRelease> $releases
     */
    private function deduplicateByRegion(Collection $releases): array
    {
        $regionMap = [];

        foreach ($releases as $release) {
            $normalizedRegion = $this->normalizeRegion($release);
            $existing = $regionMap[$normalizedRegion] ?? null;

            if ($this->shouldReplaceExisting($release, $existing)) {
                $regionMap[$normalizedRegion] = $release;
            }
        }

        return array_values($regionMap);
    }

    /**
     * @param Collection<int, GameRelease> $allReleases
     */
    private function preserveUniqueTitles(array $regionDeduplicated, Collection $allReleases): array
    {
        // Use a hash set for O(1) lookup performance.
        $titleSet = [];
        foreach ($regionDeduplicated as $release) {
            $titleSet[$this->normalizeTitle($release->title)] = true;
        }

        $result = $regionDeduplicated;

        foreach ($allReleases as $release) {
            $normalizedTitle = $this->normalizeTitle($release->title);

            if (!isset($titleSet[$normalizedTitle])) {
                $result[] = $release;
                $titleSet[$normalizedTitle] = true;
            }
        }

        return $result;
    }

    private function sortReleases(array $releases): array
    {
        usort($releases, function ($a, $b) {
            // Null dates go to the end so dated releases appear first.
            if (!$a->released_at && !$b->released_at) {
                return 0;
            }
            if (!$a->released_at) {
                return 1;
            }
            if (!$b->released_at) {
                return -1;
            }

            $dateComparison = $this->compareDates($a, $b);
            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            // When dates are equal, prefer more specific dates (day > month > year).
            return $this->compareGranularity($a->released_at_granularity, $b->released_at_granularity);
        });

        return $releases;
    }

    private function normalizeRegion(GameRelease $release): string
    {
        // Treat null, 'other', and 'worldwide' as the same region to avoid
        // duplicate worldwide releases in the UI.
        if (!$release->region || $release->region->value === 'other' || $release->region->value === 'worldwide') {
            return 'worldwide';
        }

        return $release->region->value;
    }

    private function normalizeTitle(string $title): string
    {
        return strtolower(trim($title));
    }

    private function shouldReplaceExisting(GameRelease $new, ?GameRelease $existing): bool
    {
        if (!$existing) {
            return true;
        }

        // Prefer releases with dates over those without.
        if ($new->released_at && !$existing->released_at) {
            return true;
        }

        // When both have dates, keep the earlier one.
        if ($new->released_at && $existing->released_at) {
            return $this->isEarlierRelease($new, $existing);
        }

        return false;
    }

    private function compareDates(GameRelease $a, GameRelease $b): int
    {
        $normalizedDateA = $this->normalizeDate($a->released_at, $a->released_at_granularity);
        $normalizedDateB = $this->normalizeDate($b->released_at, $b->released_at_granularity);

        return $normalizedDateA->timestamp - $normalizedDateB->timestamp;
    }

    private function compareGranularity(?ReleasedAtGranularity $a, ?ReleasedAtGranularity $b): int
    {
        $granularityOrder = ['day' => 3, 'month' => 2, 'year' => 1];
        $orderA = $a ? $granularityOrder[$a->value] : 0;
        $orderB = $b ? $granularityOrder[$b->value] : 0;

        return $orderB - $orderA;
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
