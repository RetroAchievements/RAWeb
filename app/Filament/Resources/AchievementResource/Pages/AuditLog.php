<?php

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\AchievementResource;
use App\Models\Achievement;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = AchievementResource::class;

    public function getBreadcrumbs(): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;
        $game = $achievement->game;

        return [
            route('filament.admin.resources.games.index') => 'Games',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.achievements.view', $achievement) => $achievement->title,
            'Audit Log',
        ];
    }
}
