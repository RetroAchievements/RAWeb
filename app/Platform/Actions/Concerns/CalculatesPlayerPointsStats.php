<?php

declare(strict_types=1);

namespace App\Platform\Actions\Concerns;

use App\Platform\Enums\PlayerStatType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait CalculatesPlayerPointsStats
{
    protected const PERIOD_MAP = [
        'day' => [
            'hardcore' => PlayerStatType::PointsHardcoreDay,
            'softcore' => PlayerStatType::PointsSoftcoreDay,
            'weighted' => PlayerStatType::PointsWeightedDay,
        ],
        'week' => [
            'hardcore' => PlayerStatType::PointsHardcoreWeek,
            'softcore' => PlayerStatType::PointsSoftcoreWeek,
            'weighted' => PlayerStatType::PointsWeightedWeek,
        ],
    ];

    /**
     * Get the stat intervals (windows) for calculations.
     *
     * @return array<string, Carbon>
     */
    protected function getStatIntervals(): array
    {
        return [
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
        ];
    }

    /**
     * Separate achievements into hardcore and softcore collections.
     *
     * @param Collection<int, mixed> $achievements
     * @return array{hardcore: Collection<int, mixed>, softcore: Collection<int, mixed>}
     */
    protected function separateAchievementsByType(Collection $achievements): array
    {
        return [
            'hardcore' => $achievements->filter(fn ($pa) => $pa->unlocked_hardcore_at !== null),
            'softcore' => $achievements->filter(fn ($pa) => $pa->unlocked_hardcore_at === null),
        ];
    }

    /**
     * Calculate points for achievements within a time interval (window).
     *
     * @param Collection<int, mixed> $achievements
     * @return array{points: int, points_weighted: int}
     */
    protected function calculatePointsForInterval(Collection $achievements, Carbon $interval): array
    {
        $achievementsInInterval = $achievements->filter(function ($pa) use ($interval) {
            // Convert string date to Carbon for proper comparison.
            $unlockedAt = $pa->unlocked_at instanceof Carbon
                ? $pa->unlocked_at
                : Carbon::parse($pa->unlocked_at);

            return $unlockedAt >= $interval;
        });

        // Handle both full player achievement models and simplified batch data.
        $points = 0;
        $pointsWeighted = 0;

        foreach ($achievementsInInterval as $achievement) {
            /**
             * This conditional is necessary because CalculatesPlayerPointsStats handles two different
             * data structures based on whether it's an individual write or a batch write.
             */
            if (method_exists($achievement, 'relationLoaded') && $achievement->relationLoaded('achievement')) {
                // Individual writes.
                $points += $achievement->achievement->points ?? 0;
                $pointsWeighted += $achievement->achievement->points_weighted ?? 0;
            } else {
                // Batch writes.
                $points += $achievement->points ?? 0;
                $pointsWeighted += $achievement->points_weighted ?? 0;
            }
        }

        return [
            'points' => $points,
            'points_weighted' => $pointsWeighted,
        ];
    }

    /**
     * Build stats array for a user based on their points.
     *
     * @param array{points: int, points_weighted: int} $hardcorePoints
     * @param array{points: int, points_weighted: int} $softcorePoints
     * @return array<int, array<string, mixed>>
     */
    protected function buildStatsForPeriod(
        int $userId,
        array $hardcorePoints,
        array $softcorePoints,
        string $period
    ): array {
        $stats = [];
        $statTypes = static::PERIOD_MAP[$period];

        if ($hardcorePoints['points'] > 0) {
            $stats[] = [
                'user_id' => $userId,
                'type' => $statTypes['hardcore'],
                'value' => $hardcorePoints['points'],
            ];
        }

        if ($hardcorePoints['points_weighted'] > 0) {
            $stats[] = [
                'user_id' => $userId,
                'type' => $statTypes['weighted'],
                'value' => $hardcorePoints['points_weighted'],
            ];
        }

        if ($softcorePoints['points'] > 0) {
            $stats[] = [
                'user_id' => $userId,
                'type' => $statTypes['softcore'],
                'value' => $softcorePoints['points'],
            ];
        }

        return $stats;
    }
}
