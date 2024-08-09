<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaderboardResource\Pages;

use App\Filament\Resources\LeaderboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
