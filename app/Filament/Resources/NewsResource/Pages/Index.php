<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New post'),
        ];
    }
}
