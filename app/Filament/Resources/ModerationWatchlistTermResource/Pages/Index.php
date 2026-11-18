<?php

declare(strict_types=1);

namespace App\Filament\Resources\ModerationWatchlistTermResource\Pages;

use App\Filament\Resources\ModerationWatchlistTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = ModerationWatchlistTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
