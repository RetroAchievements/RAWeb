<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Platform\Events\PlayerAchievementLocked;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Jobs\UpdateGameAchievementUnlockMediansJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchUpdateGameAchievementUnlockMediansJob implements ShouldQueue
{
    public function handle(object $event): void
    {
        $achievement = null;
        $game = null;

        switch ($event::class) {
            case PlayerAchievementLocked::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
            case PlayerAchievementUnlocked::class:
                $achievement = $event->achievement;
                $game = $achievement->game;
                break;
        }

        if ($game instanceof Game) {
            dispatch(new UpdateGameAchievementUnlockMediansJob($game->id))
                ->delay(now()->addHours(23))
                ->onQueue('achievement-metrics');
        }
    }
}
