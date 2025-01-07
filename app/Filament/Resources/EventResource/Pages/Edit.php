<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\EventResource;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
use App\Models\Event;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Event $record */
        $record = $this->record;

        $existingImage = $record->image_asset_path ?? '/Images/000001.png';

        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::GameBadge,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            $data['image_asset_path'] = $existingImage;
        }

        return $data;
    }
}
