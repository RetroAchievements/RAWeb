<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Community\Enums\CommentableType;
use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameResource;
use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Closure;
use Filament\Actions;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Game $game */
        $game = $this->getRecord();

        return "{$game->title} ({$game->system->name_short}) - Audit Log";
    }

    public function getBreadcrumb(): string
    {
        return 'Audit Log';
    }

    protected function getHeaderActions(): array
    {
        /** @var Game $record */
        $record = $this->record;

        $legacyCommentsCount = Comment::where('commentable_type', CommentableType::GameModification)
            ->where('commentable_id', $record->id)
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

        $fieldLabelMap['hash_name'] = 'File Name';
        $fieldLabelMap['hash_md5'] = 'MD5';
        $fieldLabelMap['hash_labels'] = 'Labels';
        $fieldLabelMap['hash_compatibility'] = 'Compatibility';
        $fieldLabelMap['hash_compatibility_tester_id'] = 'Compatibility Tester';
        $fieldLabelMap['hash_patch_url'] = 'Patch URL';
        $fieldLabelMap['hash_source'] = 'Resource Page URL';

        return $fieldLabelMap;
    }

    /**
     * @return Collection<string, Closure(int): string>
     */
    protected function createFieldValueMap(): Collection
    {
        $fieldValueMap = parent::createFieldValueMap();

        $fieldValueMap['hash_compatibility_tester_id'] = function (?int $userId): string {
            if (!$userId) {
                return '';
            }

            $user = User::find($userId);

            return $user?->display_name ?? "User ID: {$userId}";
        };

        return $fieldValueMap;
    }
}
