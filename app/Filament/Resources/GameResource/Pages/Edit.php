<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
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

        $action = new ApplyUploadedImageToDataAction();

        $action->execute($data, 'ImageIcon', ImageUploadType::GameBadge);
        $action->execute($data, 'ImageTitle', ImageUploadType::GameTitle);
        $action->execute($data, 'ImageIngame', ImageUploadType::GameInGame);
        $action->execute($data, 'ImageBoxArt', ImageUploadType::GameBoxArt);

        return $data;
    }
}
