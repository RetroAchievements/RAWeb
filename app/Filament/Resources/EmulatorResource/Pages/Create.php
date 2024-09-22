<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\Pages;

use App\Filament\Resources\EmulatorResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = EmulatorResource::class;
}
