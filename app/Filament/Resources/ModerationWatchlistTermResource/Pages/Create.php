<?php

declare(strict_types=1);

namespace App\Filament\Resources\ModerationWatchlistTermResource\Pages;

use App\Filament\Resources\ModerationWatchlistTermResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = ModerationWatchlistTermResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
