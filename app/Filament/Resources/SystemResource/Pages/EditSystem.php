<?php

declare(strict_types=1);

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Resources\SystemResource;
use Filament\Resources\Pages\EditRecord;

class EditSystem extends EditRecord
{
    protected static string $resource = SystemResource::class;

    public static function getNavigationLabel(): string
    {
        return __('Edit');
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
