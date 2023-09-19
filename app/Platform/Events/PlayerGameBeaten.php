<?php

declare(strict_types=1);

namespace App\Platform\Events;

use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @deprecated refactor to AchievementSetBeaten
 */
class PlayerGameBeaten
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public User $user,
        public Game $game,
        public ?bool $hardcore = false,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('channel-name');
    }
}
