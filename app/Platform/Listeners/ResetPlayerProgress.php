<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Platform\Actions\ResetPlayerProgressAction;
use App\Site\Events\UserDeleted;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResetPlayerProgress implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public object $event,
    ) {
    }

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
        app()->make(ResetPlayerProgressAction::class)->execute($event->user);
    }
}
