<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\HubResource;
use App\Models\GameSet;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = HubResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::HubBadge);

        // If no new image was uploaded, retain the existing image.
        if (!isset($data['image_asset_path'])) {
            /** @var GameSet $record */
            $record = $this->record;
            $data['image_asset_path'] = $record->image_asset_path;
        }

        return $data;
    }
}
