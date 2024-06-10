<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Filament\Pages\Page;

class Tools extends Page
{
    protected static ?int $navigationSort = 99;

    protected static ?string $navigationIcon = 'heroicon-s-wrench';

    protected static string $view = 'filament.pages.tools';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return
            $user->hasAnyRole([Role::MODERATOR, Role::ADMINISTRATOR])
            || $user->getAttribute('Permissions') >= Permissions::Moderator;
    }
}
