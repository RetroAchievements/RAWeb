<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CreateAchievementOfTheWeek
{
    public function execute(Carbon $startDate, ?array $achievementIds = null): Game
    {
        $achievementIds ??= [];

        $date = $startDate->clone();
        $year = $date->clone()->addWeeks(1)->year;

        $eventTitle = "Achievement of the Week $year";

        $eventGame = Game::firstWhere('Title', '=', $eventTitle);
        if (!$eventGame) {
            $eventGame = Game::create([
                'Title' => $eventTitle,
                'sort_title' => (new ComputeGameSortTitleAction())->execute($eventTitle),
                'Publisher' => 'RetroAchievements',
                'ConsoleID' => System::Events,
            ]);

            $nextDate = $startDate->clone()->addWeeks(52);
            while ($nextDate->year === $date->year) {
                $nextDate = $nextDate->addDays(7);
            }

            Event::create([
                'legacy_game_id' => $eventGame->ID,
                'active_from' => $startDate,
                'active_until' => $nextDate,
            ]);
        }

        $achievementCount = $eventGame->achievements()->count();
        while ($achievementCount < 52) {
            $achievementCount++;
            $achievement = Achievement::create([
                'Title' => "Week $achievementCount",
                'Description' => 'TBD',
                'MemAddr' => '0=1',
                'Points' => 1,
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $eventGame->id,
                'user_id' => EventAchievement::RAEVENTS_USER_ID,
                'BadgeName' => '00000',
                'DisplayOrder' => $achievementCount,
            ]);
        }

        $achievements = $eventGame->achievements()->orderBy('DisplayOrder')->get();

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

        $date = $startDate;
        while ($achievementCount < 64) {
            $achievementCount++;
            $achievement = Achievement::create([
                'Title' => $date->format('F') . ' Achievement of the Month',
                'Description' => 'TBD',
                'MemAddr' => '0=1',
                'Points' => 1,
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $eventGame->id,
                'user_id' => EventAchievement::RAEVENTS_USER_ID,
                'BadgeName' => '00000',
                'DisplayOrder' => $achievementCount,
            ]);

            $nextDate = $date->clone();
            do {
                $nextDate = $nextDate->addDays(7);
            } while ($nextDate->month === $date->month);

            EventAchievement::create(
                [
                    'achievement_id' => $achievement->id,
                    'source_achievement_id' => null,
                    'active_from' => $date,
                    'active_until' => $nextDate,
                ],
            );

            $date = $nextDate;
        }

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($eventGame->id))->onQueue('game-metrics');

        return $eventGame;
    }

    /**
     * @param Collection<int, Achievement> $achievements
     */
    private function createEventAchievement(int $week, Carbon $date, Collection $achievements, ?Achievement $sourceAchievement): void
    {
        $nextDate = $date->clone()->addDays(7);
        if ($week === 51) {
            while ($nextDate->year === $date->year) {
                $nextDate = $nextDate->addDays(7);
            }
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
