<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ClaimStatus: string
{
    case Active = 'active';
    case Complete = 'complete';
    case Dropped = 'dropped';
    case InReview = 'in_review';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Complete => 'Complete',
            self::Dropped => 'Dropped',
            self::InReview => 'In Review',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::Active, self::InReview => true,
            default => false,
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when ClaimStatus was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Active => 0,
            self::Complete => 1,
            self::Dropped => 2,
            self::InReview => 3,
        };
    }
}
