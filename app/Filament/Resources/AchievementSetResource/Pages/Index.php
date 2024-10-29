<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementSetResource\Pages;

use App\Filament\Resources\AchievementSetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = AchievementSetResource::class;

    protected ?string $subheading = "
        The list below contains individual sets (not games), and how those sets are
        linked to games in the database. Linking of sets to games must be done on
        the game's Manage page.
    ";

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
