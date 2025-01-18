<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserUsernameResource\Pages;

use App\Filament\Resources\UserUsernameResource;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = UserUsernameResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
