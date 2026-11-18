<?php

declare(strict_types=1);

namespace App\Enums;

enum PlayerGameActivityEventType: string
{
    case Custom = 'custom';
    case Reset = 'reset';
    case RichPresence = 'rich-presence';
    case TicketCreated = 'ticket-created';
    case Unlock = 'unlock';
}
