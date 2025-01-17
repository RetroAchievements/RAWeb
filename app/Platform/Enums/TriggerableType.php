<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum TriggerableType: string
{
    case Achievement = 'achievement';

    case Leaderboard = 'leaderboard';

    /**
     * This is actually rich presence.
     * It must be named "game" because that's where the morph is attached.
     */
    case Game = 'game';
}
