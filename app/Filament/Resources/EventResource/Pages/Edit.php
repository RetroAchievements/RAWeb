<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\EventResource;
use App\Models\Game;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::GameBadge);

        // If we have a new processed image, also update the legacy game.
        if (isset($data['image_asset_path'])) {
            /** @var Game $game */
            $game = $this->getRecord()->legacyGame;
            $game->ImageIcon = $data['image_asset_path'];
            $game->save();
        }

        return $data;
    }
}
