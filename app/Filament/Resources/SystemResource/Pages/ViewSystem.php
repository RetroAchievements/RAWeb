<?php

declare(strict_types=1);

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Resources\SystemResource;
use App\Platform\Models\System;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSystem extends ViewRecord
{
    protected static string $resource = SystemResource::class;

    public static function getNavigationLabel(): string
    {
        return __('Details');
    }

    public function getSubheading(): ?string
    {
        /** @var System $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->name_full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
