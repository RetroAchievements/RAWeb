<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
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
        $existingImage = $this->record->Image;

        if (isset($data['Image'])) {
            $data['Image'] = (new ProcessUploadedImageAction())->execute($data['Image']);
        } else {
            // If no new image was uploaded, retain the existing image.
            $data['Image'] = $existingImage;
        }

        $data['Payload'] = News::sanitizeMaybeInvalidHtml($data['Payload']);

        return $data;
    }
}
