<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;
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
                'Flags' => AchievementFlag::OfficialCore,
                'GameID' => $event->id,
                'user_id' => Comment::SYSTEM_USER_ID,
                'BadgeName' => '00000',
                'DisplayOrder' => $achievementCount,
            ]);
        }

        $achievements = $event->achievements()->orderBy('DisplayOrder')->get();

        $index = 0;
        foreach ($achievementIds as $achievementId) {
            if ($index === $achievementCount - 1) {
                $nextDate = Carbon::create($year + 1, 1, 1, 0, 0, 0);
            } else {
                $nextDate = $date->clone()->addDays(7);
            }

            $achievement = $achievements->slice($index, 1)->first();
            $sourceAchievement = Achievement::find($achievementId);

            (new UpdateEventAchievement())->execute($achievement, $sourceAchievement, $date, $nextDate);

            $date = $nextDate;
            $index++;
        }

        for ($i = $index; $i < $achievementCount; $i++) {
            if ($i === $achievementCount - 1) {
                $nextDate = Carbon::create($year + 1, 1, 1, 0, 0, 0);
            } else {
                $nextDate = $date->clone()->addDays(7);
            }

            $achievement = $achievements->slice($i, 1)->first();

            (new UpdateEventAchievement())->execute($achievement, null, $date, $nextDate);

            $date = $nextDate;
        }

        return $event;
    }
}
