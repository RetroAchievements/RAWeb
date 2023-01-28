<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Enums;

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
}
