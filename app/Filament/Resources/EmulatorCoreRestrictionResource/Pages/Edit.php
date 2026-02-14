<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorCoreRestrictionResource\Pages;

use App\Filament\Resources\EmulatorCoreRestrictionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = EmulatorCoreRestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
