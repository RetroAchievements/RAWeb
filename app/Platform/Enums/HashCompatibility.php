<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class HashCompatibility
{
    public const Unverified = 'unverified';

    public const InProgress = 'in_progress';

    public const Verified = 'verified';

    public const Problematic = 'problematic';

    public static function cases(): array
    {
        return [
            self::Unverified,
            self::InProgress,
            self::Verified,
            self::Problematic,
        ];
    }

    public static function isValid(string $compatibility): bool
    {
        return in_array($compatibility, self::cases());
    }
}
