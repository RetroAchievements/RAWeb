<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventAchievementResource\Pages;

use App\Filament\Resources\EventAchievementResource;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = EventAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
