<?php

declare(strict_types=1);

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Resources\SystemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class Details extends ViewRecord
{
    protected static string $resource = SystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
