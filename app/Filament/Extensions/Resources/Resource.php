<?php

namespace App\Filament\Extensions\Resources;

use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource as FilamentResource;

/*
 * Override default Filament resource behaviours:
 * Filament authorizes visibility of navigation items by using the viewAny policy ability.
 * We want to explicitly check for management permissions instead
 * see https://filamentphp.com/docs/3.x/panels/resources/getting-started#authorization
 */
class Resource extends FilamentResource
{
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function canViewAny(): bool
    {
        return static::can('manage');
    }

    public static function authorizeViewAny(): void
    {
        static::authorize('manage');
    }
}
