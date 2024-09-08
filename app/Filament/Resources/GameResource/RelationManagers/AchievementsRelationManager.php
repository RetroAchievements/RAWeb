<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\SyncAchievementSetOrderColumnsFromDisplayOrders;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if ($ownerRecord instanceof Game) {
            return $user->can('manage', $ownerRecord);
        }

        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('title')
                    ->description(fn (Achievement $record): string => $record->description)
                    ->wrap(),

                Tables\Columns\ViewColumn::make('MemAddr')
                    ->label('Code')
                    ->view('filament.tables.columns.achievement-code')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AchievementType::Missable => 'Missable',
                        AchievementType::Progression => 'Progression',
                        AchievementType::WinCondition => 'Win Condition',
                        default => '',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AchievementType::Missable => 'warning',
                        AchievementType::Progression => 'info',
                        AchievementType::WinCondition => 'success',
                        default => '',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('points')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('DateCreated')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DateModified')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('DisplayOrder')
                    ->toggleable(),
            ])
            ->filters([
                Filters\SelectFilter::make('Flags')
                    ->options([
                        0 => 'All',
                        AchievementFlag::OfficialCore => 'Published',
                        AchievementFlag::Unofficial => 'Unpublished',
                    ])
                    ->default(AchievementFlag::OfficialCore)
                    ->selectablePlaceholder(false)
                    ->placeholder('All')
                    ->query(function (array $data, Builder $query) {
                        if ((bool) $data['value']) {
                            $query->where('Flags', $data['value']);
                        }
                    }),
            ])
            ->headerActions([

            ])
            ->actions([
                // TODO Let developers delete achievements if they're in Unofficial and have 0 unlocks.
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('flags-core')
                        ->label('Promote selected')
                        ->icon('heroicon-o-arrow-up-right')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'Flags'])) {
                                    return;
                                }

                                $record->Flags = AchievementFlag::OfficialCore;
                                $record->save();
                            });
                        }),

                    Tables\Actions\BulkAction::make('flags-unofficial')
                        ->label('Demote selected')
                        ->icon('heroicon-o-arrow-down-right')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'Flags'])) {
                                    return;
                                }

                                $record->Flags = AchievementFlag::Unofficial;
                                $record->save();
                            });
                        }),
                ])
                    ->label('Bulk promote or demote')
                    ->visible(fn (): bool => $user->can('updateField', [Achievement::class, null, 'Flags'])),

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('type-progression')
                        ->label('Set selected to Progression')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = AchievementType::Progression;
                                $record->save();
                            });
                        }),

                    Tables\Actions\BulkAction::make('type-win-condition')
                        ->label('Set selected to Win Condition')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = AchievementType::WinCondition;
                                $record->save();
                            });
                        }),

                    Tables\Actions\BulkAction::make('type-missable')
                        ->label('Set selected to Missable')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = AchievementType::Missable;
                                $record->save();
                            });
                        }),

                    Tables\Actions\BulkAction::make('type-null')
                        ->label('Remove type from selected')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = null;
                                $record->save();
                            });
                        }),
                ])
                    ->label('Bulk set type')
                    ->visible(fn (): bool => $user->can('updateField', [Achievement::class, null, 'type'])),
            ])
            ->recordUrl(function (Achievement $record): string {
                /** @var User $user */
                $user = auth()->user();

                if ($user->can('update', $record)) {
                    return route('filament.admin.resources.achievements.edit', ['record' => $record]);
                }

                return route('filament.admin.resources.achievements.view', ['record' => $record]);
            })
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('DisplayOrder')
                    ->orderBy('DateCreated', 'asc');
            })
            ->reorderRecordsTriggerAction(
                fn (Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Stop reordering' : 'Start reordering'),
            )
            ->reorderable('DisplayOrder', $this->canReorderAchievements())
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $user->can('update', $record->loadMissing('game')),
            );
    }

    public function reorderTable(array $order): void
    {
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
            ->where('event', 'reorderedAchievements')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        // If the user didn't recently reorder achievements, write a new log.
        if (!$recentReorderingActivity) {
            activity()
                ->useLog('default')
                ->causedBy(auth()->user())
                ->performedOn($game)
                ->event('reorderedAchievements')
                ->log('Reordered Achievements');
        }

        // Double write to achievement_set_achievements to ensure it remains in sync.
        $firstAchievementId = (int) $order[0];
        $firstAchievement = Achievement::find($firstAchievementId);
        (new SyncAchievementSetOrderColumnsFromDisplayOrders())->execute($firstAchievement);
    }

    private function canReorderAchievements(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Game $game */
        $game = $this->getOwnerRecord();

        return $user->can('update', $game);
    }
}
