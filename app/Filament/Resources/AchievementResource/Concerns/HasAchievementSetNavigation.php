<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Concerns;

use App\Models\Achievement;
use Illuminate\Support\Collection;

trait HasAchievementSetNavigation
{
    /**
     * Get achievements in the same achievement set as the current record,
     * filtered by promotion status (promoted achievements only show other
     * promoted achievements, unpromoted only show unpromoted).
     * Returns null if the achievement is not in any set.
     *
     * @return Collection<int, Achievement>|null
     */
    public function getAchievementSetAchievements(): ?Collection
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;

        // Get the first achievement set for this achievement.
        $achievementSet = $achievement->achievementSets()->first();
        if (!$achievementSet) {
            return null;
        }

        // Load achievements in this set with matching promotion status.
        return $achievementSet->achievements()
            ->where('is_promoted', $achievement->is_promoted)
            ->orderBy('achievement_set_achievements.order_column')
            ->get();
    }

    /**
     * Get navigation data for the achievement set navigator component.
     * Returns null if navigation should not be shown (not in a set or only item in set).
     *
     * @return array{
     *     achievements: Collection<int, Achievement>,
     *     current: Achievement,
     *     currentIndex: int,
     *     total: int,
     *     previous: Achievement|null,
     *     next: Achievement|null
     * }|null
     */
    public function getAchievementSetNavigationData(): ?array
    {
        $achievements = $this->getAchievementSetAchievements();
        if (!$achievements || $achievements->count() <= 1) {
            return null;
        }

        /** @var Achievement $current */
        $current = $this->record;

        $currentIndex = $achievements->search(fn (Achievement $a) => $a->id === $current->id);
        if ($currentIndex === false) {
            return null;
        }

        return [
            'achievements' => $achievements,
            'current' => $current,
            'currentIndex' => $currentIndex,
            'total' => $achievements->count(),
            'previous' => $currentIndex > 0 ? $achievements[$currentIndex - 1] : null,
            'next' => $currentIndex < $achievements->count() - 1 ? $achievements[$currentIndex + 1] : null,
        ];
    }
}
