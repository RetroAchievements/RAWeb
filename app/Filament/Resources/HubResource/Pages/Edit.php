<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\Pages;

use App\Filament\Actions\ProcessUploadedImageAction;
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
        /** @var GameSet $record */
        $record = $this->record;

        $existingImage = $record->image_asset_path;

        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::HubBadge
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            $data['image_asset_path'] = $existingImage;
        }

        return $data;
    }
}
