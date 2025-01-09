<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        if (isset($data['ImageIcon'])) {
            $data['ImageIcon'] = (new ProcessUploadedImageAction())->execute(
                $data['ImageIcon'],
                ImageUploadType::GameBadge,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            unset($data['ImageIcon']);
        }

        if (isset($data['ImageTitle'])) {
            $data['ImageTitle'] = (new ProcessUploadedImageAction())->execute(
                $data['ImageTitle'],
                ImageUploadType::GameTitle,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            unset($data['ImageTitle']);
        }

        if (isset($data['ImageIngame'])) {
            $data['ImageIngame'] = (new ProcessUploadedImageAction())->execute(
                $data['ImageIngame'],
                ImageUploadType::GameInGame,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            unset($data['ImageIngame']);
        }

        if (isset($data['ImageBoxArt'])) {
            $data['ImageBoxArt'] = (new ProcessUploadedImageAction())->execute(
                $data['ImageBoxArt'],
                ImageUploadType::GameBoxArt,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            unset($data['ImageBoxArt']);
        }

        return $data;
    }
}
