<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\User;
use App\Models\UserGameBadgePreference;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GameBadgesRelationManager extends RelationManager
{
    protected static string $relationship = 'badges';
    protected static ?string $title = 'Badge History';
    protected static ?string $recordTitleAttribute = 'id';
    protected static string|BackedEnum|null $icon = 'heroicon-o-rectangle-stack';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', GameBadge::class);
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Game $game */
        $game = $ownerRecord;

        $count = $game->badges()->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Game $game */
        $game = $this->getOwnerRecord();

        // Badge history is only recorded while a set is playable. An unplayable set
        // legitimately has no rows even though it still displays an icon. Explain that
        // rather than leaving the user wondering where the badges are.
        $emptyStateDescription = $game->achievements_published > 0
            ? 'No badges have been recorded for this game yet.'
            : 'Badge history is only recorded once there are published achievements.';

        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                /** @var Builder<GameBadge> $query */
                $query
                    ->withTrashed()
                    ->with(['uploadedBy' => fn ($q) => $q->withTrashed()])
                    ->orderByDesc('became_current_at');
            })
            ->paginated(false)
            ->recordClasses(fn (GameBadge $record): ?string => $record->trashed() ? 'opacity-50' : null)
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Badge')
                    ->getStateUsing(fn (GameBadge $record): string => media_asset($record->image_asset_path))
                    ->imageWidth(96)
                    ->imageHeight(96),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->state(fn (GameBadge $record): string => $this->statusFor($record)['label'])
                    ->color(fn (GameBadge $record): string => $this->statusFor($record)['color']),

                Tables\Columns\TextColumn::make('became_current_at')
                    ->label('Set on')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploadedBy.display_name')
                    ->label('Uploader')
                    ->default('-'),
            ])
            ->emptyStateHeading('No badge history yet')
            ->emptyStateDescription($emptyStateDescription)
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->recordActions([
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove this badge?')
                    ->modalDescription('Players can no longer select it, and anyone currently displaying it switches to the game\'s current badge. You can restore it later.')
                    ->modalSubmitActionLabel('Remove')
                    ->visible(fn (GameBadge $record): bool => !$record->trashed() && $record->replaced_at !== null)
                    ->authorize(fn (GameBadge $record): bool => $user->can('delete', $record))
                    ->action(function (GameBadge $record): void {
                        DB::transaction(function () use ($record): void {
                            $record->delete();

                            // a removed badge must stop displaying on every profile that chose it
                            UserGameBadgePreference::pruneForBadges($record->game_id, [$record->sha1]);
                        });

                        $this->logBadgeActivity('removedGameBadge', 'Removed game badge', $record);

                        Notification::make()
                            ->success()
                            ->title('Badge removed')
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (GameBadge $record): bool => $record->trashed())
                    ->authorize(fn (GameBadge $record): bool => $user->can('restore', $record))
                    ->action(function (GameBadge $record): void {
                        $record->restore();

                        $this->logBadgeActivity('restoredGameBadge', 'Restored game badge', $record);

                        Notification::make()
                            ->success()
                            ->title('Badge restored')
                            ->send();
                    }),
            ]);
    }

    /**
     * Derive the label and its Filament badge color from the same underlying state so the
     * two can never drift apart. A null replaced_at is the canonical badge (the live icon).
     *
     * @return array{label: string, color: string}
     */
    private function statusFor(GameBadge $record): array
    {
        return match (true) {
            $record->trashed() => ['label' => 'Removed', 'color' => 'danger'],
            $record->replaced_at === null => ['label' => 'Current', 'color' => 'success'],
            default => ['label' => 'Replaced', 'color' => 'gray'],
        };
    }

    private function logBadgeActivity(string $event, string $message, GameBadge $record): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        activity()
            ->useLog('default')
            ->causedBy(Auth::user())
            ->performedOn($game)
            ->withProperty('attributes', ['badge' => media_asset($record->image_asset_path)])
            ->event($event)
            ->log($message);
    }
}
