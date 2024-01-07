<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class HashCompatibility
{
    public const Unverified = 'unverified';

    public const Unknown = 'unknown';

    public const Untested = 'untested';

    public const Compatible = 'compatible';

    public const Incompatible = 'incompatible';

    public static function cases(): array
    {
        return [
            self::Unverified,
            self::Unknown,
            self::Untested,
            self::Compatible,
            self::Incompatible,
        ];
    }

    public static function isValid(string $compatibility): bool
    {
        return in_array($compatibility, self::cases());
    }
}
