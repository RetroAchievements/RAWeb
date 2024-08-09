<?php

namespace App\Filament\Resources\LeaderboardResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\LeaderboardResource;
use App\Models\Leaderboard;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = LeaderboardResource::class;

    public function getBreadcrumbs(): array
    {
        /** @var Leaderboard $leaderboard */
        $leaderboard = $this->record;
        $game = $leaderboard->game;

        return [
            route('filament.admin.resources.leaderboards.index') => 'Leaderboards',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.leaderboards.view', $leaderboard) => $leaderboard->title,
            'Audit Log',
        ];
    }
}
