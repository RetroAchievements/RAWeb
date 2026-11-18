<?php

declare(strict_types=1);

namespace App\Enums;

enum PlayerGameActivitySessionType: string
{
    case Player = 'player-session';

    case Reconstructed = 'reconstructed';

    case ManualUnlock = 'manual-unlock';

    case TicketCreated = 'ticket-created';

    case Reset = 'reset';
}
