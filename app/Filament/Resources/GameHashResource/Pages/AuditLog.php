<?php

namespace App\Filament\Resources\GameHashResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameHashResource;
use App\Models\GameHash;
use Illuminate\Contracts\Support\Htmlable;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameHashResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var GameHash $gameHash */
        $gameHash = $this->getRecord();

        return "{$gameHash->name} - Audit Log";
    }

    public function getBreadcrumbs(): array
    {
        /** @var GameHash $gameHash */
        $gameHash = $this->record;
        $game = $gameHash->game;

        return [
            route('filament.admin.resources.games.index') => 'Games',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.games.hashes', $game) => 'Hashes',
            $gameHash->name,
            'Audit Log',
        ];
    }
}
