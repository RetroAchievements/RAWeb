<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventAchievementResource\Pages;

use App\Filament\Resources\EventAchievementResource;
use App\Models\EventAchievement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class Details extends ViewRecord
{
    protected static string $resource = EventAchievementResource::class;

    public function getBreadcrumbs(): array
    {
        /** @var EventAchievement $eventAchievement */
        $eventAchievement = $this->record;
        $game = $eventAchievement->achievement->game;
        $event = $game->event;

        return [
            route('filament.admin.resources.events.index') => 'Achievements',
            route('filament.admin.resources.events.view', $event) => $game->title,
            route('filament.admin.resources.event-achievements.view', $eventAchievement) => $eventAchievement->achievement->title,
            'Edit',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
