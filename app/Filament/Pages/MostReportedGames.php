<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\User;
use Filament\Pages\Page;

class MostReportedGames extends Page
{
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-s-wrench';

    protected static ?string $navigationGroup = 'Tools';

    protected static string $view = 'filament.pages.most-reported-games';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER_STAFF,
        ]);
    }
}
