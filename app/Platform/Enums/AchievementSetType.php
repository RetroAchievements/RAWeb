<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementSetType: string
{
    // TODO rename to Base, "base"
    case Core = "core";

    /**
     * Bonus sets are designed to directly complement and supplement core sets.
     * When a user loads a ROM hash for a Core set, they will be able to unlock
     * achievements from Bonus sets too.
     */
    case Bonus = "bonus";

    /**
     * Challenge sets are designed to restrict the user's playstyle as they work
     * through the core set. A user must explicitly opt-in to a challenge set, but
     * once they do, they can load a ROM hash for a Core set, and be able to unlock
     * achievements from Core and Bonus sets too.
     */
    case Challenge = "challenge";

    /**
     * Unlike Bonus sets, Specialty sets will continue to require loading a unique
     * hash. However, players will be permitted to earn achievements from the Core
     * set and any applicable Bonus/Challenge sets simultaneously when a Specialty
     * set hash is loaded.
     */
    case Specialty = "specialty";

    /**
     * Exclusive sets must be played in isolation. Like Specialty sets, Exclusive
     * sets require loading a unique hash. Players are NOT permitted to earn
     * achievements from the other sets when an Exclusive set hash is loaded.
     */
    case Exclusive = "exclusive";

    /**
     * These values are set by developers using AchievementSetsRelationManager.
     * Any will_be_* value will logically function the same as "core" until
     * multiset is enabled for the game.
     */
    case WillBeBonus = "will_be_bonus";
    case WillBeSpecialty = "will_be_specialty";
    case WillBeChallenge = "will_be_challenge";

    public function label(): string
    {
        return match ($this) {
            self::Core => 'Core',
            self::Bonus => 'Bonus',
            self::Challenge => 'Challenge',
            self::Specialty => 'Specialty',
            self::Exclusive => 'Exclusive',
            self::WillBeBonus => 'Bonus*',
            self::WillBeChallenge => 'Challenge*',
            self::WillBeSpecialty => 'Specialty*',
        };
    }
}
