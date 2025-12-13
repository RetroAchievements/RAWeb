<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Community\Enums\ArticleType;
use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameResource;
use App\Models\Comment;
use App\Models\Game;
use Filament\Actions;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Game $record */
        $record = $this->record;

        $legacyCommentsCount = Comment::where('ArticleType', ArticleType::GameModification)
            ->where('ArticleID', $record->id)
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
                ->url(route('game.modification-comment.index', ['game' => $record->id]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return Collection<string, mixed>
     */
    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['ImageIcon'] = 'Badge';

        $fieldLabelMap['release_title'] = 'Release Title';
        $fieldLabelMap['release_region'] = 'Release Region';
        $fieldLabelMap['release_date'] = 'Release Date';
        $fieldLabelMap['release_is_canonical'] = 'Is Canonical Title';

        return $fieldLabelMap;
    }
}
