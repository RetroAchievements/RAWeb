<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlaytestAwardResource\Pages;

use App\Filament\Resources\PlaytestAwardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = PlaytestAwardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
