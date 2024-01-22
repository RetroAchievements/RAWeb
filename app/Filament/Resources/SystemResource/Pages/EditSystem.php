<?php

declare(strict_types=1);

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Resources\SystemResource;
use App\Platform\Models\System;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystem extends EditRecord
{
    protected static string $resource = SystemResource::class;

    public static function getNavigationLabel(): string
    {
        return __('Edit');
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
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
