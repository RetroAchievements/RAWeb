<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\AchievementResource\Pages;
use App\Filament\Resources\AchievementResource\RelationManagers\AuthorshipCreditsRelationManager;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static ?string $navigationIcon = 'fas-trophy';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Achievement $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->id,
            'Description' => $record->description,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ID', 'Title', 'Description'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        /** @var User $user */
        $user = Auth::user();

        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 3])
                        ->schema([
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\ImageEntry::make('badge_url')
                                        ->label('Badge')
                                        ->size(config('media.icon.lg.width')),
                                    Infolists\Components\ImageEntry::make('badge_locked_url')
                                        ->label('Badge (locked)')
                                        ->size(config('media.icon.lg.width')),
                                ]),

                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('Title'),

                                    Infolists\Components\TextEntry::make('Description'),

                                    Infolists\Components\TextEntry::make('game')
                                        ->label('Game')
                                        ->formatStateUsing(fn (Game $state) => '[' . $state->id . '] ' . $state->title),

                                    Infolists\Components\TextEntry::make('developer')
                                        ->label('Author')
                                        ->formatStateUsing(fn (User $state) => $state->display_name),
                                ]),

                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('canonical_url')
                                        ->label('Canonical URL')
                                        ->url(fn (Achievement $record): string => $record->getCanonicalUrlAttribute()),

                                    Infolists\Components\TextEntry::make('permalink')
                                        ->url(fn (Achievement $record): string => $record->getPermalinkAttribute()),

                                    Infolists\Components\TextEntry::make('activeMaintainer.user.display_name')
                                        ->label('Current Maintainer')
                                        ->placeholder(fn (Achievement $record) => $record->developer->display_name . ' (original developer)')
                                        ->formatStateUsing(fn ($state, Achievement $record) => $state . ' (since ' .
                                            ($record->activeMaintainer?->effective_from?->format('Y-m-d') ?? 'N/A') . ')')
                                        ->extraAttributes(['class' => 'font-medium']),

                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('setMaintainer')
                                            ->label('Change Maintainer')
                                            ->icon('heroicon-o-user')
                                            ->form(fn (Achievement $record) => static::buildMaintainerForm($record))
                                            ->action(function (Achievement $record, array $data): void {
                                                static::handleSetMaintainer($record, $data);

                                                Notification::make()
                                                    ->success()
                                                    ->title('Success')
                                                    ->body('Set achievement maintainer.')
                                                    ->send();
                                            }),
                                    ])
                                        ->visible(fn (): bool => $user->can('assignMaintainer', [Achievement::class])),
                                ]),
                        ]),

                    Infolists\Components\Section::make([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('Created')
                            ->label('Created at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),

                        Infolists\Components\TextEntry::make('DateModified')
                            ->label('Modified at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),

                        Infolists\Components\TextEntry::make('Updated')
                            ->label('Updated at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),

                        Infolists\Components\TextEntry::make('Flags')
                            ->badge()
                            ->formatStateUsing(fn (int $state): string => match (AchievementFlag::tryFrom($state)) {
                                AchievementFlag::OfficialCore => AchievementFlag::OfficialCore->label(),
                                AchievementFlag::Unofficial => AchievementFlag::Unofficial->label(),
                                default => '',
                            })
                            ->color(fn (int $state): string => match (AchievementFlag::tryFrom($state)) {
                                AchievementFlag::OfficialCore => 'success',
                                AchievementFlag::Unofficial => 'info',
                                default => '',
                            }),

                        Infolists\Components\TextEntry::make('type')
                            ->hidden(fn ($record) => $record->game->system->id === System::Events)
                            ->badge(),

                        Infolists\Components\TextEntry::make('Points'),

                        Infolists\Components\TextEntry::make('DisplayOrder'),
                    ])->grow(false),
                ])->from('md'),

                Infolists\Components\Section::make('Event Association')
                    ->schema([
                        Infolists\Components\TextEntry::make('eventData.source_achievement_id')
                            ->label('Source Achievement')
                            ->columnSpan(2)
                            ->formatStateUsing(function (int $state): string {
                                $achievement = Achievement::find($state);

                                return "[{$achievement->id}] {$achievement->title}";
                            }),
                        Infolists\Components\TextEntry::make('eventData.active_from')
                            ->label('Active From')
                            ->date(),
                        Infolists\Components\TextEntry::make('eventData.active_through')
                            ->label('Active Through')
                            ->date(),
                    ])
                    ->columns(['xl' => 4, 'md' => 2])
                    ->hidden(fn ($record) => $record->game->system->id !== System::Events),
            ]);
    }

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        $form->model?->loadMissing('game.system');

        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 2])
                        ->schema([
                            Forms\Components\TextInput::make('Title')
                                ->required()
                                ->maxLength(64)
                                ->disabled(!$user->can('updateField', [$form->model, 'Title'])),

                            Forms\Components\TextInput::make('Description')
                                ->required()
                                ->maxLength(255)
                                ->disabled(!$user->can('updateField', [$form->model, 'Description'])),

                            Forms\Components\Select::make('GameID')
                                ->label('Game')
                                ->relationship(
                                    name: 'game',
                                    titleAttribute: 'Title',
                                )
                                ->searchable(['ID', 'Title'])
                                ->getOptionLabelFromRecordUsing(fn (Model $record) => "[{$record->ID}] {$record->Title}")
                                ->required()
                                ->disabled(!$user->can('updateField', [$form->model, 'GameID'])),

                            Forms\Components\Section::make('Maintainer')
                                ->schema([
                                    Forms\Components\Placeholder::make('current_maintainer')
                                        ->label('Current Maintainer')
                                        ->content(function (Achievement $record) {
                                            if ($record->activeMaintainer?->user) {
                                                return $record->activeMaintainer->user->display_name . ' (since ' .
                                                    $record->activeMaintainer->effective_from->format('Y-m-d') . ')';
                                            }

                                            return $record->developer->display_name . ' (original developer)';
                                        }),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('setMaintainer')
                                            ->label('Change Maintainer')
                                            ->icon('heroicon-o-user')
                                            ->form(fn (Achievement $record) => static::buildMaintainerForm($record))
                                            ->action(function (Achievement $record, array $data): void {
                                                static::handleSetMaintainer($record, $data);

                                                Notification::make()
                                                    ->success()
                                                    ->title('Success')
                                                    ->body('Set achievement maintainer.')
                                                    ->send();
                                            }),
                                    ]),
                                ])
                                ->columns(1)
                                ->visible(fn (): bool => $user->can('assignMaintainer', [Achievement::class])),
                        ]),

                    Forms\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Forms\Components\Select::make('Flags')
                                ->options([
                                    AchievementFlag::OfficialCore->value => AchievementFlag::OfficialCore->label(),
                                    AchievementFlag::Unofficial->value => AchievementFlag::Unofficial->label(),
                                ])
                                ->default(AchievementFlag::Unofficial->value)
                                ->required()
                                ->disabled(!$user->can('updateField', [$form->model, 'Flags'])),

                            Forms\Components\Select::make('type')
                                ->options(
                                    collect(AchievementType::cases())
                                        ->mapWithKeys(fn ($value) => [$value => __($value)])
                                )
                                ->hidden(fn (Achievement $record) => $record->game->system->id === System::Events)
                                ->disabled(!$user->can('updateField', [$form->model, 'type'])),

                            Forms\Components\Select::make('Points')
                                ->required()
                                ->default(0)
                                ->options(
                                    collect(AchievementPoints::cases())
                                        ->mapWithKeys(fn ($value) => [$value => $value])
                                )
                                ->disabled(!$user->can('updateField', [$form->model, 'Points'])),

                            Forms\Components\TextInput::make('DisplayOrder')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->disabled(!$user->can('updateField', [$form->model, 'DisplayOrder'])),
                        ]),
                ])->from('md'),

                Forms\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('BadgeName')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->columns(2)
                    ->hidden(!$user->can('updateField', [$form->model, 'BadgeName'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('Title')
                    ->label('Achievement')
                    ->wrap()
                    ->description(fn (Achievement $record): string => $record->description)
                    ->searchable(),

                Tables\Columns\TextColumn::make('Description')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('game')
                    ->label('Game')
                    ->formatStateUsing(fn (Game $state) => "[{$state->id}] {$state->title}")
                    ->url(fn (Game $state) => GameResource::getUrl('view', ['record' => $state->id])),

                Tables\Columns\TextColumn::make('Flags')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match (AchievementFlag::tryFrom($state)) {
                        AchievementFlag::OfficialCore => AchievementFlag::OfficialCore->label(),
                        AchievementFlag::Unofficial => AchievementFlag::Unofficial->label(),
                        default => '',
                    })
                    ->color(fn (int $state): string => match (AchievementFlag::tryFrom($state)) {
                        AchievementFlag::OfficialCore => 'success',
                        AchievementFlag::Unofficial => 'info',
                        default => '',
                    }),

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

                Tables\Columns\TextColumn::make('Points')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('TrueRatio')
                    ->label('RetroPoints')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('unlocks_total')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('unlocks_hardcore_total')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('unlock_percentage')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('unlock_hardcore_percentage')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('BadgeName')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DisplayOrder')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DateCreated')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DateModified')
                    ->label('Modified at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('Updated')
                    ->label('Updated at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('DateModified', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->multiple()
                    ->options(
                        collect(AchievementType::cases())
                            ->mapWithKeys(fn ($value) => [$value => __($value)])
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->deferFilters()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                        Tables\Actions\RestoreAction::make(),
                    ])->dropdown(false),

                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => AchievementResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuthorshipCreditsRelationManager::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\Details::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<Achievement>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['activeMaintainer.user', 'game']);
    }

    public static function buildMaintainerForm(Achievement $record): array
    {
        return [
            Forms\Components\Placeholder::make('ticket_info')
                ->hiddenLabel()
                ->content('The new maintainer will inherit any open tickets for this achievement.')
                ->extraAttributes(['style' => 'color: oklch(82.8% 0.189 84.429)']), // amber-400 (https://tailwindcss.com/docs/colors#color-palette-reference)

            Forms\Components\Select::make('user_id')
                ->label('Maintainer')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) use ($record): array {
                    $query = User::query();

                    // Bypass role checks for the original achievement author.
                    $query->where(function ($q) use ($search, $record) {
                        $q->where(function ($inner) use ($search) {
                            $inner
                                ->where('display_name', 'LIKE', "%{$search}%")
                                ->whereHas('roles', function ($roleQuery) {
                                    $roleQuery->where('name', Role::DEVELOPER);
                                });
                        })
                        ->orWhere(function ($inner) use ($search, $record) {
                            $inner
                                ->where('id', $record->user_id)
                                ->where('display_name', 'LIKE', "%{$search}%");
                        });
                    });

                    return $query->limit(50)
                        ->pluck('display_name', 'id')
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->display_name)
                ->required(),
        ];
    }

    public static function handleSetMaintainer(Achievement $record, array $data): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->can('assignMaintainer', $record)) {
            return;
        }

        $record->loadMissing('activeMaintainer');

        $oldMaintainer = $record->activeMaintainer?->user;
        $newMaintainerId = $data['user_id'];
        $newMaintainer = User::find($newMaintainerId);

        // Deactivate any existing maintainers.
        $record->maintainers()
            ->where('is_active', true)
            ->whereNull('effective_until')
            ->update([
                'is_active' => false,
                'effective_until' => now(),
            ]);

        // Create a new maintainer record.
        $record->maintainers()->create([
            'user_id' => $newMaintainerId,
            'effective_from' => now(),
            'is_active' => true,
        ]);

        // Reassign any open tickets for this achievement to the new maintainer.
        $record->tickets()
            ->unresolved()
            ->update([
                'ticketable_author_id' => $newMaintainerId,
            ]);

        activity()
            ->performedOn($record)
            ->causedBy($user)
            ->withProperties([
                'attributes' => [
                    'activeMaintainer' => [
                        'username' => $newMaintainer->username,
                        'display_name' => $newMaintainer->display_name,
                    ],
                ],
                'old' => [
                    'activeMaintainer' => [
                        'username' => $oldMaintainer?->username,
                        'display_name' => $oldMaintainer?->display_name,
                    ],
                ],
            ])
            ->event('updated')
            ->log('updated');
    }
}
