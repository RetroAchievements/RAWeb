<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\NewsResource;
use App\Models\News;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var News $record */
        $record = $this->record;

        $existingImage = $record->image_asset_path;

        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::News
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            $data['image_asset_path'] = $existingImage;
        }

        return $data;
    }
}
