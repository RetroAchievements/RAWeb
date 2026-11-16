<?php

declare(strict_types=1);

namespace App\Community\Listeners;

use App\Community\Actions\SyncAotwWinnerDiscordRolesAction;
use App\Models\EventAchievement;
use App\Models\System;
use App\Platform\Events\PlayerAchievementUnlocked;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SyncAotwWinnerDiscordRole implements ShouldQueueAfterCommit
{
    public function shouldQueue(PlayerAchievementUnlocked $event): bool
    {
        return
            $event->hardcore
            && config('services.discord.aotw_winner_role')
            && $event->achievement->game->system_id === System::Events
            && EventAchievement::currentAchievementOfTheWeek()->where('achievement_id', $event->achievement->id)->exists();
    }

    public function handle(PlayerAchievementUnlocked $event): void
    {
        app()->make(SyncAotwWinnerDiscordRolesAction::class)->execute($event->user);
    }
}
