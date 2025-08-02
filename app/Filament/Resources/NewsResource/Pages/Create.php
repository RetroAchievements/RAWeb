<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\NewsResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Create News Post';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::News);

        return $data;
    }
}
