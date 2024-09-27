<?php

declare(strict_types=1);

namespace App\Enums;

abstract class PlayerGameActivitySessionType
{
    public const Player = 'player-session';

    public const Reconstructed = 'reconstructed';

    public const ManualUnlock = 'manual-unlock';

    public const TicketCreated = 'ticket-created';

    public static function toString(string $type): string
    {
        return match ($type) {
            PlayerGameActivitySessionType::Player => "Player Session",
            PlayerGameActivitySessionType::Reconstructed => "Reconstructed Session",
            PlayerGameActivitySessionType::ManualUnlock => "Manual Unlock",
            PlayerGameActivitySessionType::TicketCreated => "Ticket Created",
            default => $type,
        };
    }
}
