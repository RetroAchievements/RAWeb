<?php

declare(strict_types=1);

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Resources\SystemResource;
use App\Support\Cache\CacheKey;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = SystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        // Strip duplicate resolution entries.
        if (!empty($data['screenshot_resolutions'])) {
            $data['screenshot_resolutions'] = collect($data['screenshot_resolutions'])
                ->unique(fn ($res) => $res['width'] . 'x' . $res['height'])
                ->values()
                ->all();
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        Cache::forget(CacheKey::SystemMenuList);

        $record->update($data);

        return $record;
    }
}
