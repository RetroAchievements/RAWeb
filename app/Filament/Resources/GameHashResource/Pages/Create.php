<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameHashResource\Pages;

use App\Filament\Resources\GameHashResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = GameHashResource::class;
}
