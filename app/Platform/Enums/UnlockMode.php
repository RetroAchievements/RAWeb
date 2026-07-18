<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
abstract class UnlockMode
{
    public const Casual = 0;

    public const Hardcore = 1;

    public static function cases(): array
    {
        return [
            self::Casual,
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
            UnlockMode::Casual => 'Casual',
            UnlockMode::Hardcore => 'Hardcore',
            default => 'Unknown',
        };
    }
}
