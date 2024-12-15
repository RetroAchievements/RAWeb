<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\NewsResource;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
use App\Models\News;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Create News Post';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['Image'])) {
            $data['Image'] = (new ProcessUploadedImageAction())->execute($data['Image'], ImageUploadType::News);
        }

        $data['Payload'] = News::sanitizeMaybeInvalidHtml($data['Payload']);

        return $data;
    }
}
