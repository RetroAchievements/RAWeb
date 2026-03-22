<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlaytestAwardResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\PlaytestAwardResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = PlaytestAwardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::PlaytestAward);

        return $data;
    }
}
