<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum PlayerProgressResetType: string
{
    /**
     * A player's account was completely reset, likely to reverse an untrack.
     */
    case Account = "account";

    /**
     * A player's unlocked achievement was reset.
     */
    case Achievement = "achievement";

    /**
     * All a player's achievements for a specific achievement set were reset.
     */
    case AchievementSet = "achievement_set";

    /**
     * A player's achievements for a specific game were reset.
     */
    case Game = "game";
}
