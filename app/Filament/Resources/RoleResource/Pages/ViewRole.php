<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Site\Models\Role;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    public static function getNavigationLabel(): string
    {
        return __('Details');
    }

    public function getSubheading(): ?string
    {
        /** @var Role $record */
        $record = $this->getRecord();

        return $record->name;
    }

    public function getHeaderActions(): array
    {
        return [
            // TODO Actions\EditAction::make(),
        ];
    }
}
