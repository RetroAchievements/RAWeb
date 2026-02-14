<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorCoreRestrictionResource\Pages;

use App\Filament\Resources\EmulatorCoreRestrictionResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = EmulatorCoreRestrictionResource::class;
}
