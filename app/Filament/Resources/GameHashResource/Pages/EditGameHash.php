<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameHashResource\Pages;

use App\Filament\Resources\GameHashResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameHash extends EditRecord
{
    protected static string $resource = GameHashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
