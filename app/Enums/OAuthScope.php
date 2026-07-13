<?php

declare(strict_types=1);

namespace App\Enums;

enum OAuthScope: string
{
    case Read = 'data:read';

    /**
     * Used by Passport::tokensCan() to document each scope.
     * These strings are not exposed to the UI.
     */
    public function description(): string
    {
        return match ($this) {
            self::Read => 'View your RetroAchievements data',
        };
    }
}
