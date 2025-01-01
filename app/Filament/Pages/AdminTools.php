<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AdminTools extends Page
{
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-s-wrench';

    protected static ?string $navigationGroup = 'Tools';

    protected static ?string $title = 'Admin';

    protected static string $view = 'filament.pages.admin-tools';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([Role::MODERATOR, Role::ADMINISTRATOR]);
    }
}
