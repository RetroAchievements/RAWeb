<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ClaimType: string
{
    case Primary = 'primary';
    case Collaboration = 'collaboration';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::Collaboration => 'Collaboration',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when ClaimType was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Primary => 0,
            self::Collaboration => 1,
        };
    }
}
