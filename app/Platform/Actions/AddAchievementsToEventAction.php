<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;

class AddAchievementsToEventAction
{
    public function execute(Event $event, int $numberOfAchievements, int $user_id): void
    {
        $displayOrder = $event->achievements->max('DisplayOrder') ?? 0;

        // create the number of requested achievements
        for ($i = 0; $i < $numberOfAchievements; $i++) {
            $displayOrder++;

            $achievement = Achievement::create([
                'Title' => 'Placeholder',
                'Description' => 'TBD',
                'MemAddr' => '0=1',
                'Points' => 1,
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $event->legacyGame->id,
                'user_id' => $user_id,
                'BadgeName' => '00000',
                'DisplayOrder' => $displayOrder,
            ]);

            EventAchievement::create([
                'achievement_id' => $achievement->id,
            ]);
        }

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($event->legacyGame->id))->onQueue('game-metrics');
    }
}
