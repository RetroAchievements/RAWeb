<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class AchievementSetType
{
    public const Core = 'core';

    public const Bonus = 'bonus';

    public const Specialty = 'specialty';

    public const WillBeBonus = 'will_be_bonus';

    public const WillBeSpecialty = 'will_be_specialty';

    public static function cases(): array
    {
        return [
            self::Core,
            self::Bonus,
            self::Specialty,
            self::WillBeBonus,
            self::WillBeSpecialty,
        ];
    }

    public static function toString(string $type): string
    {
        return match ($type) {
            self::Core => 'Core',
            self::Bonus => 'Bonus',
            self::Specialty => 'Specialty',
            self::WillBeBonus => 'Will Be Bonus',
            self::WillBeSpecialty => 'Will Be Specialty',
            default => 'Invalid state',
        };
    }
}
