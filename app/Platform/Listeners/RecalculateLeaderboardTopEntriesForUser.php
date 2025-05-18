<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Events\UserDeleted;
use App\Models\User;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
use App\Platform\Events\PlayerRankedStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecalculateLeaderboardTopEntriesForUser implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case PlayerRankedStatusChanged::class:
                $user = $event->user;
                break;
            case UserDeleted::class:
                $user = $event->user;
                break;
        }

        if (!$user instanceof User) {
            return;
        }

        (new RecalculateLeaderboardTopEntryAction())->execute(null, $user);
    }
}
