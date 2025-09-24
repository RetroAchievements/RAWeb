<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntegrationReleaseResource\Pages;

use App\Filament\Resources\IntegrationReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = IntegrationReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
