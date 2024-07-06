<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaderboardResource\Pages;

use App\Filament\Resources\LeaderboardResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class Details extends ViewRecord
{
    protected static string $resource = LeaderboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
