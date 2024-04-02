<?php

declare(strict_types=1);

namespace App\Enums;

abstract class PlayerGameActivitySessionType
{
    public const Player = 'player-session';

    public const Generated = 'generated';

    public const ManualUnlock = 'manual-unlock';
}
