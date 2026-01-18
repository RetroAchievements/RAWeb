<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Actions\ViewOnSiteAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\HubResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = HubResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make('view-on-site'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::HubBadge);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['sort_title']);
    }
}
