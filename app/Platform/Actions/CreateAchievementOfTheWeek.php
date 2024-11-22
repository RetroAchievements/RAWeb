<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CreateAchievementOfTheWeek
{
    public function execute(Carbon $startDate, ?array $achievementIds = null): Game
    {
        $achievementIds ??= [];

        $date = $startDate;
        $year = $date->clone()->addWeeks(1)->year;

        $eventTitle = "Achievement of the Week $year";

        $event = Game::firstWhere('Title', '=', $eventTitle);
        if (!$event) {
            $event = Game::Create([
                'Title' => $eventTitle,
                'sort_title' => (new ComputeGameSortTitleAction())->execute($eventTitle),
                'ConsoleID' => System::Events,
            ]);
        }

        $achievementCount = $event->achievements()->count();
        while ($achievementCount < 52) {
            $achievementCount++;
            $achievement = Achievement::Create([
                'Title' => "Week $achievementCount",
                'Description' => 'TBD',
                'MemAddr' => '0=1',
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $event->id,
                'user_id' => Comment::SYSTEM_USER_ID,
                'BadgeName' => '00000',
                'DisplayOrder' => $achievementCount,
            ]);
        }

        $achievements = $event->achievements()->orderBy('DisplayOrder')->get();

        $index = 0;
        foreach ($achievementIds as $achievementId) {
            $sourceAchievement = Achievement::find($achievementId);

            $this->createEventAchievement($index, $date, $achievements, $sourceAchievement);
            $date->addDays(7);
            $index++;
        }

        for ($i = $index; $i < $achievementCount; $i++) {
            $this->createEventAchievement($i, $date, $achievements, null);
            $date->addDays(7);
        }

        return $event;
    }

    /**
     * @param Collection<int, Achievement> $achievements
     */
    private function createEventAchievement(int $week, Carbon $date, Collection $achievements, ?Achievement $sourceAchievement): void
    {
        if ($week === $achievements->count() - 1) {
            $nextDate = Carbon::create($date->year + 1, 1, 1, 0, 0, 0);
        } else {
            $nextDate = $date->clone()->addDays(7);
        }

        $achievement = $achievements->slice($week, 1)->first();

        $eventAchievement = EventAchievement::updateOrCreate(
            ['achievement_id' => $achievement->id],
            [
                'source_achievement_id' => $sourceAchievement?->id,
                'active_from' => $date,
                'active_until' => $nextDate,
            ],
        );
    }
}
