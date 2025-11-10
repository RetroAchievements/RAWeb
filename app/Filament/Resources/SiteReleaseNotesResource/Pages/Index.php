<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteReleaseNotesResource\Pages;

use App\Filament\Resources\SiteReleaseNotesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = SiteReleaseNotesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Release Note'),
        ];
    }
}
