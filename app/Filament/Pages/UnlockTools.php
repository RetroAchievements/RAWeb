<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UnlockTools extends Page
{
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-s-lock-open';

    protected static string|UnitEnum|null $navigationGroup = 'Tools';

    protected static ?string $title = 'Unlock Tools';

    protected string $view = 'filament.pages.unlock-tools';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MANUAL_UNLOCKER,
            Role::MODERATOR,
        ]);
    }
}
