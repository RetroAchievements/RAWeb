<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Platform\Models\Achievement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAchievement extends ViewRecord
{
    protected static string $resource = AchievementResource::class;

    public static function getNavigationLabel(): string
    {
        return __('Details');
    }

    public function getSubheading(): ?string
    {
        /** @var Achievement $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->Title;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
