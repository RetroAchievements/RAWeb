<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Resources\AchievementAuthorshipCreditFormSchema;
use App\Filament\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\AchievementAuthor;
use App\Models\AchievementGroup;
use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\LogAchievementGroupActivityAction;
use App\Platform\Actions\SyncAchievementSetOrderColumnsFromDisplayOrdersAction;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\AchievementType;
use BackedEnum;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';
    protected static ?string $title = 'Achievements';
    protected static string|BackedEnum|null $icon = 'fas-trophy';

    public bool $isEditingDisplayOrders = false;

    /** @var array<int, string> */
    public array $pendingDisplayOrders = [];

    private ?AchievementSet $cachedCoreAchievementSet = null;
    private bool $isCoreAchievementSetLoaded = false;

    /** @var array<int, int|null> */
    private array $achievementGroupAssignments = [];

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if ($ownerRecord instanceof Game) {
            return $user->can('manage', $ownerRecord);
        }

        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Game $game */
        $game = $ownerRecord;

        $count = $game->achievements()->promoted()->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['activeMaintainer.user', 'developer', 'game']))
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->description(fn (Achievement $record): string => $record->description)
                    ->wrap(),

                Tables\Columns\ViewColumn::make('trigger_definition')
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
                    ->badge()
                    ->wrap(),

                Tables\Columns\TextColumn::make('points')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('modified_at')
                    ->date()
                    ->toggleable(),

                Tables\Columns\ViewColumn::make('order_column')
                    ->label('Display Order')
                    ->view('filament.tables.columns.display-order-column')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activeMaintainer')
                    ->label('Maintainer')
                    ->formatStateUsing(function (Achievement $record) {
                        return $record->activeMaintainer?->user?->display_name ?? $record->developer?->display_name;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('achievement_group')
                    ->label('Group')
                    ->getStateUsing(function (Achievement $record): string {
                        $coreSet = $this->getCoreAchievementSet();
                        if (!$coreSet) {
                            return '-';
                        }

                        $groupId = $this->achievementGroupAssignments[$record->id] ?? null;
                        if (!$groupId) {
                            return '-';
                        }

                        return $coreSet->achievementGroups->firstWhere('id', $groupId)?->label ?? '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_promoted')
                    ->label('Promoted Status')
                    ->placeholder('All')
                    ->trueLabel('Promoted')
                    ->falseLabel('Unpromoted')
                    ->default(true),

                Tables\Filters\TernaryFilter::make('duplicate_badges')
                    ->label('Has duplicate badge')
                    ->placeholder('Any')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('achievements as a2')
                                ->whereColumn('a2.game_id', 'achievements.game_id')
                                ->whereColumn('a2.image_name', 'achievements.image_name')
                                ->where('a2.id', '!=', DB::raw('achievements.id'))
                                ->whereNull('a2.deleted_at')
                                ->limit(1);
                        }),
                        false: fn (Builder $query): Builder => $query->whereNotExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('achievements as a2')
                                ->whereColumn('a2.game_id', 'achievements.game_id')
                                ->whereColumn('a2.image_name', 'achievements.image_name')
                                ->where('a2.id', '!=', DB::raw('achievements.id'))
                                ->whereNull('a2.deleted_at')
                                ->limit(1);
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Author')
                    ->options(function (): array {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        return User::withTrashed()
                            ->whereIn('id', $game->achievements()->select('user_id'))
                            ->orderBy('display_name')
                            ->pluck('display_name', 'id')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('maintainer')
                    ->label('Maintainer')
                    ->options(function (): array {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        return User::query()
                            ->whereIn('id', function ($subquery) use ($game) {
                                $subquery->select('user_id')
                                    ->from('achievement_maintainers')
                                    ->whereIn('achievement_id', $game->achievements()->select('id'))
                                    ->where('is_active', true);
                            })
                            ->orderBy('display_name')
                            ->pluck('display_name', 'id')
                            ->toArray();
                    })
                    ->query(fn (Builder $query, array $data): Builder => isset($data['value'])
                        ? $query->whereHas('activeMaintainer', fn (Builder $q) => $q->where('user_id', $data['value']))
                        : $query
                    ),
            ])
            ->headerActions([
                Actions\Action::make('cancel-edit-display-orders')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->button()
                    ->action(fn () => $this->cancelEditingDisplayOrders())
                    ->visible(fn () => $this->isEditingDisplayOrders),

                Actions\Action::make('save-display-orders')
                    ->label('Save changes')
                    ->color('primary')
                    ->button()
                    ->action(fn () => $this->saveDisplayOrders())
                    ->visible(fn () => $this->isEditingDisplayOrders),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        Actions\Action::make('move-to-top')
                            ->label('Move to Top')
                            ->icon('heroicon-o-arrow-up')
                            ->action(fn (Achievement $record) => $this->moveAchievementToPosition($record, 'top'))
                            ->visible(fn () => $this->canReorderAchievements() && !$this->isEditingDisplayOrders),

                        Actions\Action::make('move-to-bottom')
                            ->label('Move to Bottom')
                            ->icon('heroicon-o-arrow-down')
                            ->action(fn (Achievement $record) => $this->moveAchievementToPosition($record, 'bottom'))
                            ->visible(fn () => $this->canReorderAchievements() && !$this->isEditingDisplayOrders),
                    ])->dropdown(false),

                    ActionGroup::make([
                        Actions\Action::make('assign-maintainer')
                            ->label('Assign Maintainer')
                            ->icon('heroicon-o-user')
                            ->schema(fn (Achievement $record) => AchievementResource::buildMaintainerForm($record))
                            ->action(function (Achievement $record, array $data): void {
                                AchievementResource::handleSetMaintainer($record, $data);

                                Notification::make()
                                    ->title('Success')
                                    ->body('Successfully assigned maintainer to selected achievement.')
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn () => $this->canAssignMaintainer()),

                        Actions\Action::make('assign-to-group')
                            ->label('Assign to Group')
                            ->icon('heroicon-o-folder')
                            ->schema(function (Achievement $record): array {
                                $coreSet = $this->getCoreAchievementSet();
                                if (!$coreSet || $coreSet->achievementGroups->isEmpty()) {
                                    return [
                                        Text::make('No achievement groups have been created. Use the "Manage groups" button to create groups first.'),
                                    ];
                                }

                                $currentGroupId = $coreSet->achievements()
                                    ->where('achievement_id', $record->id)
                                    ->first()
                                    ?->pivot
                                    ?->achievement_group_id ?? 0;

                                $options = $coreSet->achievementGroups->pluck('label', 'id')->toArray();
                                $options[0] = '(No Group)';

                                return [
                                    Forms\Components\Select::make('achievement_group_id')
                                        ->label('Group')
                                        ->options($options)
                                        ->default($currentGroupId)
                                        ->required()
                                        ->helperText('Select a group to assign this achievement to, or select "(No Group)" to unassign it.'),
                                ];
                            })
                            ->action(function (Achievement $record, array $data): void {
                                if (!isset($data['achievement_group_id'])) {
                                    return;
                                }

                                $coreSet = $this->getCoreAchievementSet();
                                if (!$coreSet) {
                                    return;
                                }

                                $groupId = $data['achievement_group_id'] === 0 ? null : $data['achievement_group_id'];
                                $coreSet->achievements()->updateExistingPivot(
                                    $record->id,
                                    ['achievement_group_id' => $groupId]
                                );

                                // Log the assignment.
                                /** @var Game $game */
                                $game = $this->getOwnerRecord();
                                $groupLabel = $groupId
                                    ? $coreSet->achievementGroups->firstWhere('id', $groupId)?->label
                                    : null;
                                (new LogAchievementGroupActivityAction())->execute(
                                    'assignAchievements',
                                    $game,
                                    context: [
                                        'group_label' => $groupLabel,
                                        'achievement_ids' => [$record->id],
                                    ]
                                );

                                $this->invalidateCoreAchievementSetCache();

                                Notification::make()
                                    ->title('Success')
                                    ->body('Successfully assigned achievement to group.')
                                    ->success()
                                    ->send();
                            })
                            ->visible(function (): bool {
                                if (!$this->canManageAchievementGroups()) {
                                    return false;
                                }

                                $coreSet = $this->getCoreAchievementSet();

                                return $coreSet && $coreSet->achievementGroups->isNotEmpty();
                            }),
                    ])->dropdown(false),

                    DeleteAction::make(),
                ])
                    ->visible(fn (Achievement $record): bool => $this->canReorderAchievements()
                        || $this->canAssignMaintainer()
                        || $this->canManageAchievementGroups()
                        || request()->user()->can('delete', $record)
                    ),
            ])
            ->toolbarActions([
                Actions\Action::make('edit-display-orders')
                    ->label('Edit order values')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->button()
                    ->action(fn () => $this->startEditingDisplayOrders())
                    ->visible(fn () => !$this->isEditingDisplayOrders && $this->canReorderAchievements()),

                Actions\Action::make('manage-groups')
                    ->label('Manage groups')
                    ->icon('heroicon-o-folder')
                    ->color('gray')
                    ->button()
                    ->modalHeading('Manage Achievement Groups')
                    ->modalDescription('Create and organize achievement groups for this game. Groups allow you to organize achievements into collapsible sections on the game page (eg: "Final Fantasy I", "Final Fantasy II").')
                    ->modalSubmitActionLabel('Save Groups')
                    ->schema(function (): array {
                        $coreSet = $this->getCoreAchievementSet();
                        if (!$coreSet) {
                            return [
                                Text::make('This game does not have an achievement set. Groups cannot be created.'),
                            ];
                        }

                        return [
                            Forms\Components\Repeater::make('groups')
                                ->label('')
                                ->schema([
                                    Forms\Components\Hidden::make('id'),

                                    Forms\Components\TextInput::make('label')
                                        ->label('Group Name')
                                        ->placeholder('Final Fantasy II')
                                        ->required()
                                        ->maxLength(100)
                                        ->columnSpan(2),
                                ])
                                ->columns(2)
                                ->reorderable()
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemLabel(fn (array $state): string => $state['label'] ?? 'New Group')
                                ->addActionLabel('Add Group')
                                ->default(
                                    $coreSet->achievementGroups
                                        ->sortBy('order_column')
                                        ->map(fn ($g) => [
                                            'id' => $g->id,
                                            'label' => $g->label,
                                        ])
                                        ->values()
                                        ->toArray()
                                ),
                        ];
                    })
                    ->action(function (array $data): void {
                        $coreSet = $this->getCoreAchievementSet();
                        if (!$coreSet) {
                            return;
                        }

                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        $existingGroups = $coreSet->achievementGroups->keyBy('id')->toArray();
                        $existingGroupIds = array_keys($existingGroups);
                        $submittedGroupIds = [];

                        $logAction = new LogAchievementGroupActivityAction();

                        foreach ($data['groups'] ?? [] as $index => $groupData) {
                            if (!empty($groupData['id'])) {
                                // Update an existing group.
                                $group = AchievementGroup::find($groupData['id']);
                                if ($group) {
                                    $originalData = $existingGroups[$group->id] ?? [];

                                    $group->update([
                                        'label' => $groupData['label'],
                                        'order_column' => $index,
                                    ]);

                                    $logAction->execute(
                                        'update',
                                        $game,
                                        $group,
                                        $originalData,
                                        ['label' => $groupData['label'], 'order_column' => $index]
                                    );

                                    $submittedGroupIds[] = $group->id;
                                }
                            } else {
                                // Create a new group.
                                $group = AchievementGroup::create([
                                    'achievement_set_id' => $coreSet->id,
                                    'label' => $groupData['label'],
                                    'order_column' => $index,
                                ]);

                                $logAction->execute('create', $game, $group);

                                $submittedGroupIds[] = $group->id;
                            }
                        }

                        // Delete groups that were removed.
                        // This will implicitly also unassign achievements due to the DB using ON DELETE SET NULL.
                        $groupsToDelete = array_diff($existingGroupIds, $submittedGroupIds);
                        foreach ($groupsToDelete as $groupIdToDelete) {
                            $originalData = $existingGroups[$groupIdToDelete] ?? [];
                            $logAction->execute('delete', $game, null, $originalData);
                        }
                        if (!empty($groupsToDelete)) {
                            AchievementGroup::whereIn('id', $groupsToDelete)->delete();
                        }

                        $this->invalidateCoreAchievementSetCache();

                        Notification::make()
                            ->title('Success')
                            ->body('Achievement groups updated successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => !$this->isEditingDisplayOrders && $this->canManageAchievementGroups()),

                BulkActionGroup::make([
                    BulkAction::make('is-published-true')
                        ->label('Promote selected')
                        ->icon('heroicon-o-arrow-up-right')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'is_promoted'])) {
                                    return;
                                }

                                $record->is_promoted = true;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Success')
                                ->body('Successfully promoted selected achievements.')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('is-published-false')
                        ->label('Demote selected')
                        ->icon('heroicon-o-arrow-down-right')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'is_promoted'])) {
                                    return;
                                }

                                $record->is_promoted = false;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Success')
                                ->body('Successfully demoted selected achievements.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Bulk promote or demote')
                    ->visible(fn (): bool => $user->can('updateField', [Achievement::class, null, 'is_promoted'])),

                BulkActionGroup::make([
                    BulkAction::make('type-progression')
                        ->label('Set selected to Progression')
                        ->color('info')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = AchievementType::Progression;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Success')
                                ->body('Successfully set selected achievements to Progression.')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('type-win-condition')
                        ->label('Set selected to Win Condition')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = AchievementType::WinCondition;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Success')
                                ->body('Successfully set selected achievements to Win Condition.')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('type-missable')
                        ->label('Set selected to Missable')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = AchievementType::Missable;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Success')
                                ->body('Successfully set selected achievements to Missable.')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('type-null')
                        ->label('Remove type from selected')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($user) {
                            $records->each(function (Achievement $record) use ($user) {
                                if (!$user->can('updateField', [$record, 'type'])) {
                                    return;
                                }

                                $record->type = null;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Success')
                                ->body('Successfully removed type from selected achievements.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Bulk set type')
                    ->visible(function ($record) use ($user) {
                        if ($this->getOwnerRecord()->system->id === System::Events) {
                            return false;
                        }

                        return $user->can('updateField', [Achievement::class, null, 'type']);
                    }),

                BulkAction::make('add-credit')
                    ->label('Bulk add credit')
                    ->modalHeading('Bulk add credit')
                    ->color('gray')
                    ->schema(AchievementAuthorshipCreditFormSchema::getSchema())
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) use ($user) {
                        if (!$user->can('create', [AchievementAuthor::class])) {
                            return false;
                        }

                        $targetUser = User::find($data['user_id']);
                        $task = AchievementAuthorTask::from($data['task']);
                        $backdate = Carbon::parse($data['created_at']);

                        // Load all existing credit records in a single query.
                        $existingRecords = AchievementAuthor::withTrashed()
                            ->whereIn('achievement_id', $records->pluck('id'))
                            ->whereUserId($targetUser->id)
                            ->whereTask($task->value)
                            ->get()
                            ->keyBy('achievement_id');

                        $records->each(function (Achievement $record) use ($existingRecords, $targetUser, $task, $backdate) {
                            $existingRecord = $existingRecords->get($record->id);

                            if ($existingRecord) {
                                if ($existingRecord->trashed()) {
                                    $existingRecord->restore();
                                }
                                $existingRecord->created_at = $backdate;
                                $existingRecord->save();

                                return;
                            }

                            // If no existing credit record is found, create a new one.
                            $record->ensureAuthorshipCredit($targetUser, $task, $backdate);
                        });

                        Notification::make()
                            ->title('Success')
                            ->body('Successfully added credit to selected achievements.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => $user->can('create', [AchievementAuthor::class])),

                BulkAction::make('set-maintainer')
                    ->label('Assign maintainer')
                    ->color('gray')
                    ->schema(fn () => AchievementResource::buildMaintainerForm(null))
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        $records->each(function (Achievement $record) use ($data) {
                            AchievementResource::handleSetMaintainer($record, $data);
                        });

                        Notification::make()
                            ->title('Success')
                            ->body('Successfully assigned maintainer to selected achievements.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => $this->canAssignMaintainer()),

                BulkAction::make('assign-to-group')
                    ->label('Assign to group')
                    ->color('gray')
                    ->icon('heroicon-o-folder')
                    ->schema(function (): array {
                        $coreSet = $this->getCoreAchievementSet();
                        if (!$coreSet || $coreSet->achievementGroups->isEmpty()) {
                            return [
                                Text::make('No achievement groups have been created for this achievement set. Use the "Manage groups" button to create groups first.'),
                            ];
                        }

                        $options = $coreSet->achievementGroups->pluck('label', 'id')->toArray();
                        $options[0] = '(No Group)';

                        return [
                            Forms\Components\Select::make('achievement_group_id')
                                ->label('Group')
                                ->options($options)
                                ->required()
                                ->helperText('Select a group to assign the selected achievements to, or select "(No Group)" to unassign them.'),
                        ];
                    })
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        if (!isset($data['achievement_group_id'])) {
                            return;
                        }

                        $coreSet = $this->getCoreAchievementSet();
                        if (!$coreSet) {
                            return;
                        }

                        $groupId = $data['achievement_group_id'] === 0 ? null : $data['achievement_group_id'];

                        $achievementIds = [];
                        $records->each(function (Achievement $record) use ($coreSet, $groupId, &$achievementIds) {
                            $coreSet->achievements()->updateExistingPivot(
                                $record->id,
                                ['achievement_group_id' => $groupId]
                            );
                            $achievementIds[] = $record->id;
                        });

                        /** @var Game $game */
                        $game = $this->getOwnerRecord();
                        $groupLabel = $groupId
                            ? $coreSet->achievementGroups->firstWhere('id', $groupId)?->label
                            : null;
                        (new LogAchievementGroupActivityAction())->execute(
                            'assignAchievements',
                            $game,
                            context: [
                                'group_label' => $groupLabel,
                                'achievement_ids' => $achievementIds,
                            ]
                        );

                        $this->invalidateCoreAchievementSetCache();

                        Notification::make()
                            ->title('Success')
                            ->body('Successfully assigned achievements to group.')
                            ->success()
                            ->send();
                    })
                    ->visible(function (): bool {
                        if (!$this->canManageAchievementGroups()) {
                            return false;
                        }

                        $coreSet = $this->getCoreAchievementSet();

                        return $coreSet && $coreSet->achievementGroups->isNotEmpty();
                    }),
            ])
            ->recordUrl(function (Achievement $record) use ($user): ?string {
                if ($this->isEditingDisplayOrders) {
                    return null;
                }

                if ($this->canUpdateAnyAchievement() || $user->can('update', $record)) {
                    return route('filament.admin.resources.achievements.edit', ['record' => $record]);
                }

                return route('filament.admin.resources.achievements.view', ['record' => $record]);
            })
            ->paginated([400])
            ->defaultPaginationPageOption(400)
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('order_column')
                    ->orderBy('created_at', 'asc');
            })
            ->reorderRecordsTriggerAction(
                fn (Actions\Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Done dragging' : 'Drag to reorder')
                    ->visible(fn () => $this->canReorderAchievements() && !$this->isEditingDisplayOrders),
            )
            ->reorderable('order_column', $this->canReorderAchievements() && !$this->isEditingDisplayOrders)
            ->selectable(!$this->isEditingDisplayOrders);
    }

    public function reorderTable(array $order, string|int|null $draggedRecordKey = null): void
    {
        parent::reorderTable($order, $draggedRecordKey);

        $this->syncAndLogReorder($order);
    }

    private function syncAndLogReorder(array $order): void
    {
        $this->logReorderingActivity();

        $firstAchievementId = (int) $order[0];
        $firstAchievement = Achievement::find($firstAchievementId);
        if ($firstAchievement) {
            (new SyncAchievementSetOrderColumnsFromDisplayOrdersAction())->execute($firstAchievement);
        }
    }

    private function moveAchievementToPosition(Achievement $record, string $position): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        $achievements = $game->achievements()->orderBy('order_column')->get();

        if ($position === 'top') {
            $minOrder = $achievements->min('order_column') ?? 0;

            if ($minOrder > 0) {
                $newOrder = $minOrder - 1;
            } else {
                $game->achievements()->where('id', '!=', $record->id)->increment('order_column');
                $newOrder = 0;
            }
        } else {
            $maxOrder = $achievements->max('order_column') ?? 0;
            $newOrder = $maxOrder + 1;
        }

        $record->order_column = $newOrder;
        $record->save();

        (new SyncAchievementSetOrderColumnsFromDisplayOrdersAction())->execute($record);
        $this->logReorderingActivity();

        Notification::make()
            ->title('Success')
            ->body("Achievement moved to {$position}.")
            ->success()
            ->send();
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
            ->where('event', 'reorderedAchievements')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        // If the user didn't recently reorder achievements, write a new log.
        if (!$recentReorderingActivity) {
            activity()
                ->useLog('default')
                ->causedBy($user)
                ->performedOn($game)
                ->event('reorderedAchievements')
                ->log('Reordered Achievements');
        }
    }

    public function startEditingDisplayOrders(): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        // Pre-populate with current values so inputs have initial values.
        $this->pendingDisplayOrders = $game->achievements()
            ->pluck('order_column', 'id')
            ->map(fn ($value) => (string) $value)
            ->toArray();

        $this->isEditingDisplayOrders = true;

        $this->flushCachedTableRecords();
    }

    public function saveDisplayOrders(): void
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        // Get original values to detect actual changes.
        $originalOrders = $game->achievements()->pluck('order_column', 'id')->toArray();

        $changedAchievements = [];
        foreach ($this->pendingDisplayOrders as $id => $newOrder) {
            $newOrderInt = (int) $newOrder;
            if (!isset($originalOrders[$id]) || $originalOrders[$id] !== $newOrderInt) {
                $changedAchievements[$id] = $newOrderInt;
            }
        }

        if (empty($changedAchievements)) {
            $this->cancelEditingDisplayOrders();

            return;
        }

        // Update all changed achievements in a single query.
        $updates = [];
        foreach ($changedAchievements as $id => $newOrder) {
            $updates[] = ['id' => $id, 'order_column' => $newOrder];
        }
        Achievement::upsert($updates, ['id'], ['order_column']);

        // Be sure we also sync the achievement set order column values.
        $firstAchievement = Achievement::find(array_key_first($changedAchievements));
        if ($firstAchievement) {
            (new SyncAchievementSetOrderColumnsFromDisplayOrdersAction())->execute($firstAchievement);
        }

        $this->logReorderingActivity();

        $this->isEditingDisplayOrders = false;
        $this->pendingDisplayOrders = [];
        $this->flushCachedTableRecords();

        Notification::make()
            ->title('Display orders updated')
            ->success()
            ->send();
    }

    public function cancelEditingDisplayOrders(): void
    {
        $this->isEditingDisplayOrders = false;
        $this->pendingDisplayOrders = [];

        $this->flushCachedTableRecords();
    }

    private function canReorderAchievements(): bool
    {
        /** @var User */
        $user = Auth::user();

        return once(function () use ($user) {
            if ($user->can('updateField', [Achievement::class, null, 'order_column'])) {
                return true;
            }

            // Junior developers can reorder if they have an active claim on this game.
            if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
                /** @var Game $game */
                $game = $this->getOwnerRecord();

                return $user->hasActiveClaimOnGameId($game->id);
            }

            return false;
        });
    }

    private function canAssignMaintainer(): bool
    {
        /** @var User */
        $user = Auth::user();

        return once(fn () => $user->can('assignMaintainer', Achievement::class));
    }

    private function canManageAchievementGroups(): bool
    {
        /** @var User */
        $user = Auth::user();

        return once(fn () => $user->can('manage', AchievementGroup::class));
    }

    private function canUpdateAnyAchievement(): bool
    {
        /** @var User */
        $user = Auth::user();

        return once(fn () => $user->can('updateAny', Achievement::class));
    }

    private function invalidateCoreAchievementSetCache(): void
    {
        $this->cachedCoreAchievementSet = null;
        $this->isCoreAchievementSetLoaded = false;
        $this->achievementGroupAssignments = [];
    }

    private function getCoreAchievementSet(): ?AchievementSet
    {
        if ($this->isCoreAchievementSetLoaded) {
            return $this->cachedCoreAchievementSet;
        }

        /** @var Game $game */
        $game = $this->getOwnerRecord();

        $query = $game->gameAchievementSets()
            ->where('type', AchievementSetType::Core)
            ->with('achievementSet.achievementGroups');

        $this->cachedCoreAchievementSet = $query->first()?->achievementSet;
        $this->isCoreAchievementSetLoaded = true;

        // Pre-load all achievement group assignments in a single query.
        if ($this->cachedCoreAchievementSet) {
            $this->achievementGroupAssignments = AchievementSetAchievement::query()
                ->where('achievement_set_id', $this->cachedCoreAchievementSet->id)
                ->pluck('achievement_group_id', 'achievement_id')
                ->toArray();
        }

        return $this->cachedCoreAchievementSet;
    }
}
