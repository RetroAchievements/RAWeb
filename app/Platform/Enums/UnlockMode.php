<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class UnlockMode
{
    public const Softcore = 0;

    public const Hardcore = 1;

    public static function cases(): array
    {
        return [
            self::Softcore,
            self::Hardcore,
        ];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::cases());
    }

    public static function toString(int $type): string
    {
        return match ($type) {
            UnlockMode::Softcore => 'Softcore',
            UnlockMode::Hardcore => 'Hardcore',
            default => 'Unknown',
        };
    }
}
