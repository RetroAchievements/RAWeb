<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Actions\CloneLeaderboardAction;
use App\Filament\Actions\DeleteLeaderboardAction;
use App\Filament\Actions\ResetAllLeaderboardEntriesAction;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\ValueFormat;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaderboardsRelationManager extends RelationManager
{
    protected static string $relationship = 'leaderboards';

    protected static string|BackedEnum|null $icon = 'fas-bars-staggered';

    public bool $isEditingDisplayOrders = false;

    /** @var array<int, string> */
    public array $pendingDisplayOrders = [];

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', Leaderboard::class);
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->leaderboards->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

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
                    ->placeholder(fn (Leaderboard $record): string => $record->description)
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

                Tables\Columns\ViewColumn::make('DisplayOrder')
                    ->view('filament.tables.columns.display-order-column')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('state')
                    ->label('State')
                    ->formatStateUsing(fn (LeaderboardState $state): string => ucfirst($state->value)),
            ])
            ->searchPlaceholder('Search (ID, Title)')
            ->recordUrl(function (Leaderboard $record): ?string {
                if ($this->isEditingDisplayOrders) {
                    return null;
                }

                /** @var User $user */
                $user = Auth::user();

                if ($user->can('update', $record)) {
                    return route('filament.admin.resources.leaderboards.edit', ['record' => $record]);
                }

                return route('filament.admin.resources.leaderboards.view', ['record' => $record]);
            })
            ->filters([

            ])
            ->headerActions([
                Action::make('cancel-edit-display-orders')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->action(fn () => $this->cancelEditingDisplayOrders())
                    ->visible(fn () => $this->isEditingDisplayOrders),

                Action::make('save-display-orders')
                    ->label('Save changes')
                    ->color('primary')
                    ->action(fn () => $this->saveDisplayOrders())
                    ->visible(fn () => $this->isEditingDisplayOrders),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_entries')
                        ->label('View entries')
                        ->url(fn (Leaderboard $leaderboard) => route('filament.admin.resources.leaderboards.view', ['record' => $leaderboard]))
                        ->visible(function (Leaderboard $leaderboard) use ($user) {
                            return $user->can('manage', $leaderboard) && !$user->can('update', $leaderboard);
                        }),
                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-s-pencil')
                        ->url(fn (Leaderboard $leaderboard) => route('filament.admin.resources.leaderboards.edit', ['record' => $leaderboard]))
                        ->visible(function (Leaderboard $leaderboard) use ($user) {
                            return $user->can('update', $leaderboard);
                        }),
                    Action::make('move-to-top')
                        ->label('Move to Top')
                        ->icon('heroicon-o-arrow-up')
                        ->action(fn (Leaderboard $leaderboard) => $this->moveLeaderboardToPosition($leaderboard, 'top'))
                        ->visible(fn () => $this->canReorderLeaderboards() && !$this->isEditingDisplayOrders),

                    Action::make('move-to-bottom')
                        ->label('Move to Bottom')
                        ->icon('heroicon-o-arrow-down')
                        ->action(fn (Leaderboard $leaderboard) => $this->moveLeaderboardToPosition($leaderboard, 'bottom'))
                        ->visible(fn () => $this->canReorderLeaderboards() && !$this->isEditingDisplayOrders),
                    Action::make('promote-leaderboard')
                        ->label('Promote')
                        ->icon('heroicon-s-arrow-up-right')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Leaderboard $leaderboard) {
                            $leaderboard->state = LeaderboardState::Active;
                            $leaderboard->push();

                            Notification::make()
                                ->success()
                                ->title('Leaderboard promoted')
                                ->send();
                        })
                        ->visible(function (Leaderboard $leaderboard) use ($user) {
                            return $user->can('updateField', [$leaderboard, 'state']) && $leaderboard->state !== LeaderboardState::Active;
                        }),
                    Action::make('demote-leaderboard')
                        ->label('Demote')
                        ->icon('heroicon-s-arrow-down-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Leaderboard $leaderboard) {
                            $leaderboard->state = LeaderboardState::Unpublished;
                            $leaderboard->push();
                            Notification::make()
                                ->success()
                                ->title('Leaderboard demoted')
                                ->send();
                        })
                        ->visible(function (Leaderboard $leaderboard) use ($user) {
                            return $user->can('updateField', [$leaderboard, 'state']) && $leaderboard->state !== LeaderboardState::Unpublished;
                        }),
                    CloneLeaderboardAction::make('clone_leaderboard'),
                    ResetAllLeaderboardEntriesAction::make('delete_all_entries'),
                    DeleteLeaderboardAction::make('delete_leaderboard'),
                ]),
            ])
            ->toolbarActions([
                Action::make('edit-display-orders')
                    ->label('Edit order values')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->action(fn () => $this->startEditingDisplayOrders())
                    ->visible(fn () => !$this->isEditingDisplayOrders && $this->canReorderLeaderboards()),
                BulkActionGroup::make([
                    BulkAction::make('promote_leaderboards')
                        ->label('Promote selected')
                        ->icon('heroicon-s-arrow-up-right')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Builder $query) use ($user) {
                            $leaderboards = $query->get();

                            foreach ($leaderboards as $leaderboard) {
                                if (!$user->can('updateField', [$leaderboard, 'state'])) {
                                    return;
                                }

                                $leaderboard->state = LeaderboardState::Active;
                                $leaderboard->push();
                            }

                            Notification::make()
                                ->success()
                                ->title('Leaderboards promoted')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('demote_leaderboards')
                        ->label('Demote selected')
                        ->icon('heroicon-s-arrow-down-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Builder $query) use ($user) {
                            $leaderboards = $query->get();

                            foreach ($leaderboards as $leaderboard) {
                                if (!$user->can('updateField', [$leaderboard, 'state'])) {
                                    return;
                                }

                                $leaderboard->state = LeaderboardState::Unpublished;
                                $leaderboard->push();
                            }

                            Notification::make()
                                ->success()
                                ->title('Leaderboards demoted')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ])
                ->label('Bulk promote or demote')
                ->visible(fn (): bool => $user->can('updateField', [Leaderboard::class, null, 'state'])),
            ])
            ->paginated([400])
            ->defaultPaginationPageOption(400)
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('DisplayOrder')
                    ->orderBy('Created', 'asc');
            })
            ->reorderRecordsTriggerAction(
                fn (Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Done dragging' : 'Drag to reorder')
                    ->visible(!$this->isEditingDisplayOrders),
            )
            ->reorderable('DisplayOrder', $this->canReorderLeaderboards() && !$this->isEditingDisplayOrders)
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => !$this->isEditingDisplayOrders && $user->can('update', $record->loadMissing('game')),
            );
    }

    public function reorderTable(array $order, string|int|null $draggedRecordKey = null): void
    {
        // Do not automatically adjust the DisplayOrder of hidden leaderboards (DisplayOrder < 0).
        $order = array_filter($order, function (string $leaderboardId) {
            $leaderboard = Leaderboard::find((int) $leaderboardId);

            return $leaderboard && $leaderboard->DisplayOrder >= 0;
        });

        parent::reorderTable($order, $draggedRecordKey);

        $this->logReorderingActivity();
    }

    public function startEditingDisplayOrders(): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        $leaderboards = $game->leaderboards()
            ->where('DisplayOrder', '>=', 0)
            ->get();

        $this->pendingDisplayOrders = $leaderboards
            ->mapWithKeys(fn (Leaderboard $lb) => [$lb->ID => (string) $lb->DisplayOrder])
            ->all();

        $this->isEditingDisplayOrders = true;
        $this->resetTable();
    }

    public function saveDisplayOrders(): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        $leaderboards = $game->leaderboards()
            ->where('DisplayOrder', '>=', 0)
            ->get()
            ->keyBy('ID');

        $leaderboardsToUpdate = [];
        foreach ($this->pendingDisplayOrders as $leaderboardId => $newOrder) {
            $leaderboard = $leaderboards->get($leaderboardId);
            if ($leaderboard && (int) $newOrder !== $leaderboard->DisplayOrder) {
                $leaderboardsToUpdate[] = [
                    'ID' => $leaderboardId,
                    'DisplayOrder' => (int) $newOrder,
                ];
            }
        }

        if (!empty($leaderboardsToUpdate)) {
            foreach ($leaderboardsToUpdate as $update) {
                Leaderboard::where('ID', $update['ID'])->update(['DisplayOrder' => $update['DisplayOrder']]);
            }
            $this->logReorderingActivity();
        }

        $this->isEditingDisplayOrders = false;
        $this->pendingDisplayOrders = [];
        $this->resetTable();

        Notification::make()
            ->title('Display orders updated')
            ->success()
            ->send();
    }

    public function cancelEditingDisplayOrders(): void
    {
        $this->isEditingDisplayOrders = false;
        $this->pendingDisplayOrders = [];
        $this->resetTable();
    }

    private function moveLeaderboardToPosition(Leaderboard $leaderboard, string $position): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        $visibleLeaderboards = $game->leaderboards()
            ->where('DisplayOrder', '>=', 0)
            ->orderBy('DisplayOrder')
            ->get();

        if ($position === 'top') {
            $minOrder = $visibleLeaderboards->min('DisplayOrder');
            if ($minOrder > 0) {
                $leaderboard->update(['DisplayOrder' => $minOrder - 1]);
            } else {
                foreach ($visibleLeaderboards as $lb) {
                    if ($lb->ID !== $leaderboard->ID) {
                        $lb->increment('DisplayOrder');
                    }
                }
                $leaderboard->update(['DisplayOrder' => 0]);
            }
        } else {
            $maxOrder = $visibleLeaderboards->max('DisplayOrder');
            $leaderboard->update(['DisplayOrder' => $maxOrder + 1]);
        }

        $this->logReorderingActivity();
        $this->resetTable();

        Notification::make()
            ->title('Leaderboard moved to ' . $position)
            ->success()
            ->send();
    }

    private function canReorderLeaderboards(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Game $game */
        $game = $this->getOwnerRecord();

        return $user->can('update', $game);
    }

    private function logReorderingActivity(): void
    {
        /** @var User $user */
        $user = Auth::user();
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
                ->causedBy($user)
                ->performedOn($game)
                ->event('reorderedLeaderboards')
                ->log('Reordered Leaderboards');
        }
    }
}
