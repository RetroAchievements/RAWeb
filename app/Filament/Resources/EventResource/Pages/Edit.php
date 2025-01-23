<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\EventResource;
use App\Models\Game;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::GameBadge,
            );

            /** @var Game $game */
            $game = $this->getRecord()->legacyGame;
            $game->ImageIcon = $data['image_asset_path'];
            $game->save();
        } else {
            // If no new image was uploaded, retain the existing image.
            unset($data['image_asset_path']);
        }

        return $data;
    }
}
