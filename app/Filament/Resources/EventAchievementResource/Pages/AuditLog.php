<?php

namespace App\Filament\Resources\EventAchievementResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\EventAchievementResource;
use App\Models\EventAchievement;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = EventAchievementResource::class;

    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['active_until'] = 'Active Until';

        return $fieldLabelMap;
    }

    public function getBreadcrumbs(): array
    {
        /** @var EventAchievement $eventAchievement */
        $eventAchievement = $this->record;
        $game = $eventAchievement->achievement->game;
        $event = $game->event;

        return [
            route('filament.admin.resources.events.index') => 'Events',
            route('filament.admin.resources.events.view', $event) => $game->title,
            route('filament.admin.resources.event-achievements.view', $eventAchievement) => $eventAchievement->achievement->title,
            'Audit Log',
        ];
    }
}
