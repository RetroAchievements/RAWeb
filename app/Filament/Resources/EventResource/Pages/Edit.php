<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Resources\EventResource;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = EventResource::class;
}
