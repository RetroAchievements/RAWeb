<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\Pages;

use App\Community\Enums\ArticleType;
use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\HubResource;
use App\Models\Comment;
use App\Models\GameSet;
use Filament\Actions;
use Filament\Support\Enums\IconPosition;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = HubResource::class;

    protected function getHeaderActions(): array
    {
        /** @var GameSet $record */
        $record = $this->record;

        // Newer hubs don't have a game ID, thus won't have any legacy comments.
        if (!$record->game_id) {
            return [];
        }

        $legacyCommentsCount = Comment::where('ArticleType', ArticleType::GameModification)
            ->where('ArticleID', $record->game_id)
            ->count();

        if ($legacyCommentsCount === 0) {
            return [];
        }

        return [
            Actions\Action::make('view-legacy-comments')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->iconPosition(IconPosition::After)
                ->color('gray')
                ->label("View Legacy Audit Comments ({$legacyCommentsCount})")
                ->url(route('game.modification-comment.index', ['game' => $record->game_id]))
                ->openUrlInNewTab(),
        ];
    }
}
