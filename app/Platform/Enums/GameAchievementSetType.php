<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class GameAchievementSetType
{
    public const Core = 'core';

    public const Bonus = 'bonus';

    public const Challenge = 'challenge';

    public const WillBeBonus = 'will_be_bonus'; // temporary

    public const WillBeChallenge = 'will_be_challenge'; // temporary

    public static function cases(): array
    {
        return [
            self::Core,
            self::Bonus,
            self::Challenge,
            self::WillBeBonus,
            self::WillBeChallenge,
        ];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::cases());
    }
}
