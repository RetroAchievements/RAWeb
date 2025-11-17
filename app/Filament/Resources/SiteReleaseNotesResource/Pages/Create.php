<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteReleaseNotesResource\Pages;

use App\Community\Enums\NewsCategory;
use App\Filament\Resources\SiteReleaseNotesResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = SiteReleaseNotesResource::class;
    protected static bool $canCreateAnother = false;
    protected static ?string $title = 'Create Release Note';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['category'] = NewsCategory::SiteReleaseNotes->value;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
