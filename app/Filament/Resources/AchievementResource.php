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
use BackedEnum;
use Closure;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-trophy';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

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

    public static function infolist(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->components([
                Schemas\Components\Flex::make([
                    Infolists\Components\ImageEntry::make('badge_url')
                        ->label('Icon')
                        ->size(config('media.icon.md.width')),

                    Infolists\Components\ImageEntry::make('badge_locked_url')
                        ->label('Icon (Locked)')
                        ->size(config('media.icon.md.width')),
                ])->from('md'),

                Schemas\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('Title'),

                        Infolists\Components\TextEntry::make('Description'),

                        Infolists\Components\TextEntry::make('game')
                            ->label('Game')
                            ->formatStateUsing(fn (Game $state) => "[{$state->id}] {$state->title} ({$state->system->name_short})")
                            ->url(fn (Game $state): string => GameResource::getUrl('view', ['record' => $state->id]))
                            ->extraAttributes(['class' => 'underline']),

                        Infolists\Components\TextEntry::make('developer')
                            ->label('Author')
                            ->formatStateUsing(fn (User $state) => $state->display_name)
                            ->url(fn (User $state): string => route('user.show', $state->display_name))
                            ->extraAttributes(['class' => 'underline']),

                        Infolists\Components\TextEntry::make('permalink')
                            ->url(fn (Achievement $record): string => $record->getPermalinkAttribute())
                            ->extraAttributes(['class' => 'underline'])
                            ->openUrlInNewTab(),
                    ]),

                Schemas\Components\Section::make('Classification')
                    ->icon('heroicon-o-tag')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('Flags')
                            ->label('Published Status')
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
                            ->label('Type')
                            ->badge()
                            ->placeholder('none')
                            ->formatStateUsing(fn (?string $state): string => $state ? __('achievement-type.' . $state) : '')
                            ->color(fn (?string $state): string => match ($state) {
                                AchievementType::Missable => 'warning',
                                AchievementType::Progression => 'info',
                                AchievementType::WinCondition => 'success',
                                default => 'gray',
                            })
                            ->hidden(fn ($record) => $record->game->system->id === System::Events),

                        Infolists\Components\TextEntry::make('Points'),

                        Infolists\Components\TextEntry::make('DisplayOrder'),
                    ]),

                Schemas\Components\Section::make('Maintainer')
                    ->icon('heroicon-o-user')
                    ->description('The developer responsible for maintaining this achievement. All new tickets will be assigned to the maintainer.')
                    ->schema([
                        Infolists\Components\TextEntry::make('activeMaintainer.user.display_name')
                            ->label('Current Maintainer')
                            ->placeholder(fn (Achievement $record) => $record->developer->display_name . ' (original developer)')
                            ->formatStateUsing(fn ($state, Achievement $record) => $state . ' (since ' .
                                ($record->activeMaintainer?->effective_from?->format('Y-m-d') ?? 'N/A') . ')')
                            ->extraAttributes(['class' => 'font-medium']),

                        Schemas\Components\Actions::make([
                            Actions\Action::make('setMaintainer')
                                ->label('Change Maintainer')
                                ->icon('heroicon-o-user')
                                ->schema(fn (Achievement $record) => static::buildMaintainerForm($record))
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
                    ])
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4]),

                Schemas\Components\Section::make('Event Association')
                    ->icon('heroicon-o-calendar')
                    ->columns(['md' => 2, 'xl' => 4])
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
                    ->hidden(fn ($record) => $record->game->system->id !== System::Events),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        $schema->model?->loadMissing('game.system');

        return $schema
            ->components([
                Schemas\Components\Section::make('Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->required()
                            ->maxLength(64)
                            ->disabled(!$user->can('updateField', [$schema->model, 'Title'])),

                        Forms\Components\TextInput::make('Description')
                            ->required()
                            ->maxLength(255)
                            ->disabled(!$user->can('updateField', [$schema->model, 'Description'])),
                    ]),

                Schemas\Components\Section::make('Classification')
                    ->icon('heroicon-o-tag')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\Select::make('Flags')
                            ->label('Published Status')
                            ->options([
                                AchievementFlag::OfficialCore->value => AchievementFlag::OfficialCore->label(),
                                AchievementFlag::Unofficial->value => AchievementFlag::Unofficial->label(),
                            ])
                            ->required()
                            ->disabled(!$user->can('updateField', [$schema->model, 'Flags'])),

                        Forms\Components\Select::make('Points')
                            ->required()
                            ->default(0)
                            ->options(
                                collect(AchievementPoints::cases())
                                    ->mapWithKeys(fn ($value) => [$value => $value])
                            )
                            ->disabled(!$user->can('updateField', [$schema->model, 'Points'])),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->placeholder('None')
                            ->options(function (?Achievement $record) {
                                $canHaveBeatenTypes = $record?->getCanHaveBeatenTypes() ?? true;

                                $types = collect(AchievementType::cases());
                                if (!$canHaveBeatenTypes) {
                                    $types = $types->filter(fn ($type) => $type === AchievementType::Missable);
                                }

                                return $types->mapWithKeys(fn ($value) => [$value => __('achievement-type.' . $value)]);
                            })
                            ->helperText('A game is considered beaten if ALL Progression achievements are unlocked and ANY Win Condition achievements are unlocked.')
                            ->hidden(fn (?Achievement $record) => !System::isGameSystem($record?->game?->system?->id ?? 0))
                            ->disabled(!$user->can('updateField', [$schema->model, 'type'])),
                    ]),

                Schemas\Components\Section::make('Video (Deprecated)')
                    ->icon('heroicon-o-video-camera')
                    ->description(new HtmlString('This field is deprecated and will be replaced with on-site guides. <a href="https://github.com/RetroAchievements/RAWeb/discussions/4196" target="_blank" class="underline">See RFC</a>'))
                    ->schema([
                        Forms\Components\TextInput::make('AssocVideo')
                            ->label('Video URL')
                            ->maxLength(255)
                            ->disabled(!$user->can('updateField', [$schema->model, 'AssocVideo']))
                            ->rules([
                                fn (?Achievement $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                    // Only allow clearing the field, not changing to a different value.
                                    if (!empty($value) && $value !== $record?->AssocVideo) {
                                        $fail('This field is deprecated. You can only clear it, not change it to a different URL.');
                                    }
                                },
                            ]),
                    ])
                    ->visible(fn (?Achievement $record): bool => !empty($record?->AssocVideo)),

                Schemas\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('BadgeName')
                            ->label('Icon')
                            ->disk('livewire-tmp')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->columns(2)
                    ->hidden(!$user->can('updateField', [$schema->model, 'BadgeName'])),

                Schemas\Components\Section::make('Maintainer')
                    ->icon('heroicon-o-user')
                    ->description('The developer responsible for maintaining this achievement. All new tickets will be assigned to the maintainer.')
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

                        Schemas\Components\Actions::make([
                            Actions\Action::make('setMaintainer')
                                ->label('Change Maintainer')
                                ->icon('heroicon-o-user')
                                ->schema(fn (Achievement $record) => static::buildMaintainerForm($record))
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
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->visible(fn (): bool => $user->can('assignMaintainer', [Achievement::class])),
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
                    ->wrap()
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
                    ->badge()
                    ->wrap(),

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
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ActionGroup::make([
                        Actions\ViewAction::make(),
                        Actions\EditAction::make(),
                        Actions\DeleteAction::make(),
                        Actions\RestoreAction::make(),
                    ])->dropdown(false),

                    Actions\Action::make('audit-log')
                        ->url(fn ($record) => AchievementResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
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

    public static function buildMaintainerForm(?Achievement $record): array
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
                    // Bypass role checks for the original achievement author.
                    return User::search($search)
                        ->take(50)
                        ->get()
                        ->filter(function ($user) use ($record) {
                            return $user->hasRole(Role::DEVELOPER) || ($record && $user->id === $record->user_id);
                        })
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
