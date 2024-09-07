<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\GameResource\Actions\ProcessUploadedImageAction;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        if (isset($data['ImageIcon'])) {
            $data['ImageIcon'] = (new ProcessUploadedImageAction())->execute($data['ImageIcon']);
        }

        return $data;
    }
}
