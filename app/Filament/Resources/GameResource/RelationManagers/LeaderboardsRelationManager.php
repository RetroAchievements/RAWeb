<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Actions\DeleteLeaderboardAction;
use App\Filament\Actions\ResetAllLeaderboardEntriesAction;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaderboardsRelationManager extends RelationManager
{
    protected static string $relationship = 'leaderboards';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('manage', Leaderboard::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('Title')
                    ->label('Title')
                    ->description(fn (Leaderboard $record): string => $record->description)
                    ->searchable(),

                Tables\Columns\TextColumn::make('Format')
                    ->label('Format')
                    ->formatStateUsing(fn (string $state) => ValueFormat::toString($state))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('entries_count')
                    ->label('Entries')
                    ->counts('entries')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('LowerIsBetter')
                    ->label('Lower Is Better')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DisplayOrder')
                    ->label('Display Order')
                    ->color(fn ($record) => $record->DisplayOrder < 0 ? 'danger' : null)
                    ->toggleable(),
            ])
            ->searchPlaceholder('Search (ID, Title)')
            ->filters([

            ])
            ->headerActions([

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('view_entries')
                        ->label('View entries')
                        ->url(fn (Leaderboard $leaderboard) => route('filament.admin.resources.leaderboards.view', ['record' => $leaderboard]))
                        ->visible(function (Leaderboard $leaderboard) {
                            /** @var User $user */
                            $user = auth()->user();

                            return $user->can('manage', $leaderboard) && !$user->can('update', $leaderboard);
                        }),

                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-s-pencil')
                        ->url(fn (Leaderboard $leaderboard) => route('filament.admin.resources.leaderboards.edit', ['record' => $leaderboard]))
                        ->visible(function (Leaderboard $leaderboard) {
                            /** @var User $user */
                            $user = auth()->user();

                            return $user->can('update', $leaderboard);
                        }),

                    ResetAllLeaderboardEntriesAction::make('delete_all_entries'),
                    DeleteLeaderboardAction::make('delete_leaderboard'),
                ]),
            ])
            ->bulkActions([

            ])
            ->paginated([25, 50, 100])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('DisplayOrder')
                    ->orderBy('Created', 'asc');
            })
            ->reorderRecordsTriggerAction(
                fn (Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Stop reordering' : 'Start reordering'),
            )
            ->reorderable('DisplayOrder', $this->canReorderLeaderboards())
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $user->can('update', $record->loadMissing('game')),
            );
    }

    public function reorderTable(array $order): void
    {
        // Do not automatically adjust the DisplayOrder of hidden leaderboards (DisplayOrder < 0).
        $order = array_filter($order, function (string $leaderboardId) {
            $leaderboard = Leaderboard::find((int) $leaderboardId);

            return $leaderboard && $leaderboard->DisplayOrder >= 0;
        });

        parent::reorderTable($order);

        /** @var User $user */
        $user = auth()->user();
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        // We don't want to flood the logs with reordering activity.
        // We'll throttle these events by 10 minutes.
        $recentReorderingActivity = DB::table('audit_log')
            ->where('causer_id', $user->id)
            ->where('subject_id', $game->id)
            ->where('subject_type', 'game')
            ->where('event', 'reorderedLeaderboards')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        // If the user didn't recently reorder leaderboards, write a new log.
        if (!$recentReorderingActivity) {
            activity()
                ->useLog('default')
                ->causedBy(auth()->user())
                ->performedOn($game)
                ->event('reorderedLeaderboards')
                ->log('Reordered Leaderboards');
        }
    }

    private function canReorderLeaderboards(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Leaderboard $game */
        $game = $this->getOwnerRecord();

        return $user->can('update', $game);
    }
}
