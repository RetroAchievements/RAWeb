<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientSupportLevel: int
{
    // client is recognized and has no limitations.
    case Full = 0;

    // client is recognized, but only allowed to do softcore unlocks.
    case Outdated = 1;

    // client is recognized, and not allowed to do anything.
    case Blocked = 2;

    // client is not recognized.
    case Unknown = 3;

    // client is recognized, but not officially supported.
    case Unsupported = 4;

    // client is recognized and has known issues. a warning is shown,
    // but hardcore unlocks and leaderboard submissions are still allowed.
    case Warned = 5;

    /**
     * Full and Warned levels both permit hardcore unlocks and leaderboard submissions.
     * All other levels restrict these capabilities in some way.
     */
    public function allowsHardcoreUnlocks(): bool
    {
        return $this === self::Full || $this === self::Warned;
    }
}
