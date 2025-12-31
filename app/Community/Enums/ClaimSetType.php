<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ClaimSetType: string
{
    case NewSet = 'new_set';
    case Revision = 'revision';

    public function label(): string
    {
        return match ($this) {
            self::NewSet => 'New',
            self::Revision => 'Revision',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when ClaimSetType was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::NewSet => 0,
            self::Revision => 1,
        };
    }
}
