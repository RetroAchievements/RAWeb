<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameScreenshotModerationResource\Pages;

use App\Filament\Resources\GameScreenshotModerationResource;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = GameScreenshotModerationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
