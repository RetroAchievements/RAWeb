<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Models\Achievement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class Details extends ViewRecord
{
    protected static string $resource = AchievementResource::class;

    public function getBreadcrumbs(): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;
        $game = $achievement->game;

        return [
            route('filament.admin.resources.achievements.index') => 'Achievements',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.achievements.view', $achievement) => $achievement->title,
            'View',
        ];
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
