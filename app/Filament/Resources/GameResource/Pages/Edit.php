<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\GameResource;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        $imageTypes = [
            'ImageIcon' => ImageUploadType::GameBadge,
            'ImageTitle' => ImageUploadType::GameTitle,
            'ImageIngame' => ImageUploadType::GameInGame,
            'ImageBoxArt' => ImageUploadType::GameBoxArt,
        ];

        foreach ($imageTypes as $field => $uploadType) {
            if (isset($data[$field])) {
                $data[$field] = (new ProcessUploadedImageAction())->execute(
                    $data[$field],
                    $uploadType,
                );
            } else {
                // If no new image was uploaded, retain the existing image.
                unset($data[$field]);
            }
        }

        return $data;
    }
}
