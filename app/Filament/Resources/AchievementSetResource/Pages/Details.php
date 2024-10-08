<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementSetResource\Pages;

use App\Filament\Resources\AchievementSetResource;
use Filament\Resources\Pages\ViewRecord;

class Details extends ViewRecord
{
    protected static string $resource = AchievementSetResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
