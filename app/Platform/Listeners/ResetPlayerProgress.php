<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Events\UserDeleted;
use App\Platform\Actions\ResetPlayerProgress as ResetPlayerProgressAction;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPlayerProgress implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;

        switch ($event::class) {
            case UserDeleted::class:
                $user = $event->user;
                break;
        }

        if ($user === null) {
            return;
        }

        // reset all achievements earned by the player
        app()->make(ResetPlayerProgressAction::class)->execute($user);
    }
}
