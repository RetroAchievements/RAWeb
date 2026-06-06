<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Models\Game;
use App\Models\User;

class ClaimWithUnresolvedTicketsAlert extends Alert
{
    public function __construct(
        public readonly User $user,
        public readonly Game $game,
        public readonly int $ticketCount,
    ) {
    }

    /**
     * "[Scott](<https://retroachievements.org/user/Scott>) created a claim on [Sonic the Hedgehog](<https://retroachievements.org/game/1>) with [2 open tickets](<https://retroachievements.org/user/Scott/tickets>)"
     */
    public function toDiscordMessage(): string
    {
        $userUrl = route('user.show', ['user' => $this->user]);
        $gameUrl = route('game.show', ['game' => $this->game]);
        $ticketsUrl = route('developer.tickets', ['user' => $this->user->display_name]);

        return sprintf(
            '[%s](<%s>) created a claim on [%s](<%s>) with [%d open tickets](<%s>)',
            $this->user->display_name,
            $userUrl,
            $this->game->title,
            $gameUrl,
            $this->ticketCount,
            $ticketsUrl,
        );
    }
}
