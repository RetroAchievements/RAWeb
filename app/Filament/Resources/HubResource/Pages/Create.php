<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\Pages;

use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\HubResource;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
use App\Platform\Enums\GameSetType;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = HubResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::HubBadge
            );
        } else {
            $data['image_asset_path'] = '/Images/000001.png';
        }

        $data['type'] = GameSetType::Hub->value;

        return $data;
    }
}
