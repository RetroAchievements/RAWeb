<?php

declare(strict_types=1);

namespace App\Enums;

enum PlayerGameActivitySessionType: string
{
    case Player = 'player-session';

    case Reconstructed = 'reconstructed';

    case ManualUnlock = 'manual-unlock';

    case TicketCreated = 'ticket-created';

    public function label(): string
    {
        return match ($this) {
            self::Player => 'Player Session',
            self::Reconstructed => 'Reconstructed Session',
            self::ManualUnlock => 'Manual Unlock',
            self::TicketCreated => 'Ticket Created',
        };
    }
}
