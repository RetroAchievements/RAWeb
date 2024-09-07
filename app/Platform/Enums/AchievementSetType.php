<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementSetType: string
{
    case Core = "core";

    /**
     * Bonus sets are designed to directly complement and supplement core sets.
     * When a user loads a ROM hash for a Core set, they will be able to unlock
     * achievements from Bonus sets too.
     */
    case Bonus = "bonus";

    /**
     * Unlike Bonus sets, Specialty sets will continue to require loading a unique
     * hash. However, players will be permitted to earn achievements from the Core
     * set and any applicable Bonus sets simultaneously when a Specialty set hash
     * is loaded.
     */
    case Specialty = "specialty";

    /**
     * Exclusive sets must be played in isolation. Like Specialty sets, Exclusive
     * sets require loading a unique hash. Players are NOT permitted to earn
     * achievements from the Core set or Bonus sets when an Exclusive set hash
     * is loaded.
     */
    case Exclusive = "exclusive";

    /**
     * TODO
     *
     * 1- These values will be set by developers using a new Filament-based tool,
     *    likely a wizard of some kind. Any will_be_* value will logically function
     *    the same as "core".
     *
     * 2- A gradual rollout/pilot phase can be run with a few games, if desired.
     *    This can be done by changing a few game sets' will_be_[type] values to just [type].
     *
     * 3- When it's time to make multiset generally-available to all players, all will_be_*
     *    values can transition to the real thing and these enum values can be removed.
     */
    case WillBeBonus = "will_be_bonus";
    case WillBeSpecialty = "will_be_specialty";
    case WillBeExclusive = "will_be_exclusive";
}
