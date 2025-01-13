<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Http\Data\AchievementOfTheWeekProgressData;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\PlayerAchievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalculateAchievementOfTheWeekUserProgressAction
{
    public function execute(User $user, Event $event): AchievementOfTheWeekProgressData
    {
        $allWeeklyAchievements = $this->getAllWeeklyAchievements($event);
        $userUnlocks = $this->getUserUnlocks($user, $allWeeklyAchievements->pluck('achievement_id'));

        // Find current achievement.
        $currentAchievement = $allWeeklyAchievements->first(function ($achievement) {
            return
                Carbon::parse($achievement['active_from'])->lte(now())
                && Carbon::parse($achievement['active_until'])->gt(now())
            ;
        });

        if (!$currentAchievement) {
            return new AchievementOfTheWeekProgressData(
                streakLength: 0,
                hasCurrentWeek: false,
                hasActiveStreak: false,
            );
        }

        $currentIndex = $allWeeklyAchievements->search(fn ($achievement) => $achievement->id === $currentAchievement->id);
        $streak = 0;

        // Start from the week before current and go backwards.
        for ($i = $currentIndex - 1; $i >= 0; $i--) {
            $achievement = $allWeeklyAchievements[$i];
            $isUnlocked = $userUnlocks->has($achievement->achievement_id);

            if ($isUnlocked) {
                $streak++;
            } else {
                break;
            }
        }

        // If they completed last week, they have an active streak that could continue.
        $hasLastWeek = $currentIndex > 0 && $userUnlocks->has($allWeeklyAchievements[$currentIndex - 1]->achievement_id);
        $hasCurrentWeek = $userUnlocks->has($currentAchievement->achievement_id);

        return new AchievementOfTheWeekProgressData(
            streakLength: $streak + ($hasCurrentWeek ? 1 : 0),
            hasCurrentWeek: $hasCurrentWeek,
            hasActiveStreak: $hasLastWeek,
        );
    }

    /**
     * @return EloquentCollection<int, EventAchievement>
     */
    private function getAllWeeklyAchievements(Event $event): EloquentCollection
    {
        /** @var EloquentCollection<int, EventAchievement> */
        return $event->achievements()
            ->whereNotNull('active_from')
            ->whereNotNull('active_until')
            ->whereRaw(dateCompareStatement('active_until', 'active_from', '< 20')) // Filter out monthly achievements.
            ->orderBy('active_from')
            ->get();
    }

    /**
     * @param Collection<int, int> $achievementIds
     * @return Collection<int, PlayerAchievement>
     */
    private function getUserUnlocks(User $user, Collection $achievementIds): Collection
    {
        return $user->playerAchievements()
            ->whereIn('achievement_id', $achievementIds)
            ->whereNotNull('unlocked_at')
            ->get()
            ->keyBy('achievement_id');
    }
}
