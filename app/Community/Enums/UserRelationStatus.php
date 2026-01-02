<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum UserRelationStatus: string
{
    case Blocked = 'blocked';
    case NotFollowing = 'not_following';
    case Following = 'following';

    public function label(): string
    {
        return match ($this) {
            self::Blocked => 'Blocked',
            self::NotFollowing => 'Not following',
            self::Following => 'Following',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when UserRelationship was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Blocked => -1,
            self::NotFollowing => 0,
            self::Following => 1,
        };
    }
}
