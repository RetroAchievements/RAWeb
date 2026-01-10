<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Concerns;

use App\Models\Achievement;
use Illuminate\Support\Collection;

trait HasAchievementSetNavigation
{
    /**
     * Get navigation data for the achievement set navigator component.
     * Returns null if navigation should not be shown (achievement not in a set,
     * or only one achievement total in the set).
     *
     * Returns both promoted and unpromoted achievements for full context,
     * while prev/next navigation stays within the current promotion status.
     *
     * @return array{
     *     promotedAchievements: Collection<int, Achievement>,
     *     unpromotedAchievements: Collection<int, Achievement>,
     *     current: Achievement,
     *     isPromoted: bool,
     *     currentIndex: int,
     *     totalInStatus: int,
     *     previous: Achievement|null,
     *     next: Achievement|null
     * }|null
     */
    public function getAchievementSetNavigationData(): ?array
    {
        /** @var Achievement $current */
        $current = $this->record;

        $achievementSet = $current->achievementSets()->first();
        if (!$achievementSet) {
            return null;
        }

        $allAchievements = $achievementSet->achievements()
            ->orderBy('achievement_set_achievements.order_column')
            ->get();
        if ($allAchievements->count() <= 1) {
            return null;
        }

        // Split into promoted and unpromoted groups.
        $promotedAchievements = $allAchievements->filter(fn (Achievement $a) => $a->is_promoted)->values();
        $unpromotedAchievements = $allAchievements->filter(fn (Achievement $a) => !$a->is_promoted)->values();

        // Get the achievements in the current promotion status for prev/next navigation.
        $isPromoted = (bool) $current->is_promoted;
        $sameStatusAchievements = $isPromoted ? $promotedAchievements : $unpromotedAchievements;

        $currentIndex = $sameStatusAchievements->search(fn (Achievement $a) => $a->id === $current->id);
        if ($currentIndex === false) {
            return null;
        }

        return [
            'promotedAchievements' => $promotedAchievements,
            'unpromotedAchievements' => $unpromotedAchievements,
            'current' => $current,
            'isPromoted' => $isPromoted,
            'currentIndex' => $currentIndex,
            'totalInStatus' => $sameStatusAchievements->count(),
            'previous' => $currentIndex > 0 ? $sameStatusAchievements[$currentIndex - 1] : null,
            'next' => $currentIndex < $sameStatusAchievements->count() - 1 ? $sameStatusAchievements[$currentIndex + 1] : null,
        ];
    }
}
