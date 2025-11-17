<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteReleaseNotesResource\Pages;

use App\Community\Enums\NewsCategory;
use App\Filament\Resources\SiteReleaseNotesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = SiteReleaseNotesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['category'] = NewsCategory::SiteReleaseNotes->value;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
