<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Scope identifiers are a permanent public API contract. Once a third-party app
 * requests a scope by name, that name can never be renamed or restructured.
 *
 * New scopes follow the same "resource:action" shape.
 *
 * "data:read" is the umbrella read scope for data a signed-out visitor could
 * already see on the website.
 *
 * Access to anything sensitive or writable must always ship as a new scope,
 * never be folded into an existing one, so that existing grants never silently
 * gain power and users re-consent to see exactly what is newly being requested.
 */
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
            self::Read => 'View publicly visible RetroAchievements data',
        };
    }
}
