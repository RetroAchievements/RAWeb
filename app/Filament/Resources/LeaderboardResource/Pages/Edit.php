<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaderboardResource\Pages;

use App\Filament\Resources\LeaderboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = LeaderboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
