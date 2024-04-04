<?php

declare(strict_types=1);

namespace App\Platform\Events;

use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PlayerSessionHeartbeat
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public User $user,
        public Game $game,
        public ?string $message = null,
        public ?GameHash $gameHash = null,
        public ?Carbon $timestamp = null,
        public ?string $userAgent = null,
        public ?string $ipAddr = null,
    ) {
        $this->timestamp ??= Carbon::now();

        $request = request();
        if ($request) {
            $this->userAgent ??= $request->header('User-Agent', '[not provided]');
            $this->ipAddr ??= $request->ip();
        }
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('channel-name');
    }
}
