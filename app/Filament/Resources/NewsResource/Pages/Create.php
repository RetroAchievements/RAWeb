<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Create News Post';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['Image'])) {
            $data['Image'] = (new ProcessUploadedImageAction())->execute($data['Image']);
        }

        return $data;
    }
}
