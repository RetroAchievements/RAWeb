<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteAwardResource\Pages;

use App\Filament\Resources\SiteAwardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = SiteAwardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
