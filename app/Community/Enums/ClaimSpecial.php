<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ClaimSpecial: string
{
    case None = 'none';
    case OwnRevision = 'own_revision';
    case FreeRollout = 'free_rollout';
    case ScheduledRelease = 'scheduled_release';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::OwnRevision => 'Own Revision',
            self::FreeRollout => 'Free Rollout',
            self::ScheduledRelease => 'Release Scheduled',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when ClaimSpecial was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::None => 0,
            self::OwnRevision => 1,
            self::FreeRollout => 2,
            self::ScheduledRelease => 3,
        };
    }
}
