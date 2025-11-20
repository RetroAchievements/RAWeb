<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MostReportedGames extends Page
{
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-s-wrench';

    protected static string|UnitEnum|null $navigationGroup = 'Tools';

    protected string $view = 'filament.pages.most-reported-games';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ]);
    }
}
