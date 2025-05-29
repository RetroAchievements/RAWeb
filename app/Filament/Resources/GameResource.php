<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\GameResource\Pages;
use App\Filament\Resources\GameResource\RelationManagers\AchievementSetsRelationManager;
use App\Filament\Resources\GameResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Resources\GameResource\RelationManagers\CoreSetAuthorshipCreditsRelationManager;
use App\Filament\Resources\GameResource\RelationManagers\LeaderboardsRelationManager;
use App\Filament\Resources\GameResource\RelationManagers\MemoryNotesRelationManager;
use App\Filament\Resources\GameResource\RelationManagers\ReleasesRelationManager;
use App\Filament\Rules\ExistsInForumTopics;
use App\Filament\Rules\IsAllowedGuideUrl;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'fas-gamepad';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Game $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    /**
     * @param Game $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->id,
            'System' => $record->system->name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ID', 'Title'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ImageEntry::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.lg.width')),

                Infolists\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('permalink')
                            ->url(fn (Game $record): string => $record->getPermalinkAttribute())
                            ->extraAttributes(['class' => 'underline'])
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('title'),

                        Infolists\Components\TextEntry::make('sort_title')
                            ->label('Sort Title'),

                        Infolists\Components\TextEntry::make('forumTopic.id')
                            ->label('Forum Topic ID')
                            ->url(fn (?int $state) => $state ? route('forum-topic.show', ['topic' => $state]) : null)
                            ->placeholder('none')
                            ->extraAttributes(function (Game $game): array {
                                if ($game->forumTopic?->id) {
                                    return ['class' => 'underline'];
                                }

                                return [];
                            }),

                        Infolists\Components\TextEntry::make('system')
                            ->formatStateUsing(fn (System $state) => "[{$state->id}] {$state->name}")
                            ->url(function (System $state): ?string {
                                if (request()->user()->can('manage', System::class)) {
                                    return SystemResource::getUrl('view', ['record' => $state->id]);
                                }

                                return null;
                            })
                            ->extraAttributes(function (): array {
                                if (request()->user()->can('manage', System::class)) {
                                    return ['class' => 'underline'];
                                }

                                return [];
                            }),
                    ]),

                Infolists\Components\Section::make('Metadata')
                    ->icon('heroicon-c-information-circle')
                    ->description('While optional, this metadata can help more players find the game. It also gets fed to various apps plugged in to the RetroAchievements API.')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('Developer'),

                        Infolists\Components\TextEntry::make('Publisher'),

                        Infolists\Components\TextEntry::make('Genre'),

                        Infolists\Components\TextEntry::make('GuideURL')
                            ->label('RAGuide URL')
                            ->placeholder('none')
                            ->url(fn (Game $record): ?string => $record->GuideURL)
                            ->extraAttributes(function (Game $game): array {
                                if ($game->GuideURL) {
                                    return ['class' => 'underline'];
                                }

                                return [];
                            })
                            ->limit(30),
                    ]),

                Infolists\Components\Section::make('Metrics')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->description("
                        Statistics regarding the game's players and achievements can be found here.
                    ")
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\Fieldset::make('Players')
                            ->schema([
                                Infolists\Components\TextEntry::make('players_total')
                                    ->label('Total')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('players_hardcore')
                                    ->label('Hardcore')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Infolists\Components\Fieldset::make('Achievements')
                            ->schema([
                                Infolists\Components\TextEntry::make('achievements_published')
                                    ->label('Published')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('achievements_unpublished')
                                    ->label('Unofficial')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Infolists\Components\Fieldset::make('Score')
                            ->schema([
                                Infolists\Components\TextEntry::make('points_total')
                                    ->label('Points')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('TotalTruePoints')
                                    ->label('RetroPoints')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->label('Canonical Title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(80)
                            ->disabled(!$user->can('updateField', [$form->model, 'Title'])),

                        Forms\Components\TextInput::make('sort_title')
                            ->required()
                            ->label('Sort Title')
                            ->minLength(2)
                            ->disabled(!$user->can('updateField', [$form->model, 'sort_title']))
                            ->helperText('Normalized title for sorting purposes. For example, "The Goonies II" would sort as "goonies 02". DON\'T CHANGE THIS UNLESS YOU KNOW WHAT YOU\'RE DOING.')
                            ->reactive()
                            ->afterStateHydrated(function (callable $set, callable $get, ?string $state) {
                                $set('original_sort_title', $state ?? '');
                            }),

                        Forms\Components\TextInput::make('ForumTopicID')
                            ->label('Forum Topic ID')
                            ->numeric()
                            ->rules([new ExistsInForumTopics()])
                            ->disabled(!$user->can('updateField', [$form->model, 'ForumTopicID'])),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->icon('heroicon-c-information-circle')
                    ->description('While optional, this metadata can help more players find the game. It also gets fed to various apps plugged in to the RetroAchievements API.')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('Developer')
                            ->maxLength(50)
                            ->disabled(!$user->can('updateField', [$form->model, 'Developer'])),

                        Forms\Components\TextInput::make('Publisher')
                            ->maxLength(50)
                            ->disabled(!$user->can('updateField', [$form->model, 'Publisher'])),

                        Forms\Components\TextInput::make('Genre')
                            ->maxLength(50)
                            ->disabled(!$user->can('updateField', [$form->model, 'Genre'])),

                        Forms\Components\TextInput::make('GuideURL')
                            ->label('RAGuide URL')
                            ->url()
                            ->rules([new IsAllowedGuideUrl()])
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->disabled(!$user->can('updateField', [$form->model, 'GuideURL'])),
                    ]),

                Forms\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('ImageIcon')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->rules([
                                'dimensions:width=96,height=96',
                            ])
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),

                        Forms\Components\FileUpload::make('ImageBoxArt')
                            ->label('Box Art')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),

                        Forms\Components\FileUpload::make('ImageTitle')
                            ->label('Title')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),

                        Forms\Components\FileUpload::make('ImageIngame')
                            ->label('In Game')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->columns(2),
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('system')
                    ->label('System')
                    ->formatStateUsing(fn (System $state) => "[{$state->id}] {$state->name}")
                    ->url(function (System $state) {
                        if (request()->user()->can('manage', System::class)) {
                            return SystemResource::getUrl('view', ['record' => $state->id]);
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('forumTopic.id')
                    ->label('Forum Topic')
                    ->url(fn (?int $state) => $state ? route('forum-topic.show', ['topic' => $state]) : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('Publisher')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('Developer')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('Genre')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('players_total')
                    ->label('Players (Total)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('players_hardcore')
                    ->label('Players (Hardcore)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('achievements_published')
                    ->label('Achievements (Published)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('achievements_unpublished')
                    ->label('Achievements (Unofficial)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('points_total')
                    ->label('Points')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('TotalTruePoints')
                    ->label('RetroPoints')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('system')
                    ->options(function () {
                        $options = ['active' => 'All Active Systems'];
                        $systemOptions = System::orderBy('Name')
                            ->pluck('Name', 'ID')
                            ->toArray();

                        return $options + $systemOptions;
                    })
                    ->query(function (Builder $query, $data) {
                        $value = $data['value'] ?? null;

                        if ($value === 'active') {
                            $query->whereIn('ConsoleID', System::active()->pluck('ID'));
                        } elseif ($value) {
                            $query->where('ConsoleID', $value);
                        }
                    }),

                Tables\Filters\TernaryFilter::make('achievements_published')
                    ->label('Has core set')
                    ->placeholder('Any')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('achievements_published', '>=', 6),
                        false: fn (Builder $query): Builder => $query->where('achievements_published', '<', 6),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                Tables\Filters\SelectFilter::make('media')
                    ->label('Media')
                    ->placeholder('Select a value')
                    ->options([
                        'none' => 'Has all media',
                        'all' => 'Missing all media',
                        'any' => 'Missing any media',
                        'badge' => 'Missing badge icon',
                        'boxart' => 'Missing box art',
                        'title' => 'Missing title image',
                        'ingame' => 'Missing in-game image',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $query = $query->whereNotIn('ConsoleID', System::getNonGameSystems());

                        switch ($data['value']) {
                            case 'none':
                                return $query->whereNotNull('ImageIcon')
                                    ->where('ImageIcon', '!=', '/Images/000001.png')
                                    ->whereNotNull('ImageTitle')
                                    ->where('ImageTitle', '!=', '/Images/000002.png')
                                    ->whereNotNull('ImageIngame')
                                    ->where('ImageIngame', '!=', '/Images/000002.png')
                                    ->whereNotNull('ImageBoxArt')
                                    ->where('ImageBoxArt', '!=', '/Images/000002.png');
                            case 'all':
                                return $query->where(function ($query) {
                                    $query->whereNull('ImageIcon')
                                        ->orWhere('ImageIcon', '/Images/000001.png');
                                })->where(function ($query) {
                                    $query->whereNull('ImageTitle')
                                        ->orWhere('ImageTitle', '/Images/000002.png');
                                })->where(function ($query) {
                                    $query->whereNull('ImageIngame')
                                        ->orWhere('ImageIngame', '/Images/000002.png');
                                })->where(function ($query) {
                                    $query->whereNull('ImageBoxArt')
                                        ->orWhere('ImageBoxArt', '/Images/000002.png');
                                });
                            case 'any':
                                return $query->where(function ($query) {
                                    $query->whereNull('ImageIcon')
                                        ->orWhere('ImageIcon', '/Images/000001.png')
                                        ->orWhereNull('ImageTitle')
                                        ->orWhere('ImageTitle', '/Images/000002.png')
                                        ->orWhereNull('ImageIngame')
                                        ->orWhere('ImageIngame', '/Images/000002.png')
                                        ->orWhereNull('ImageBoxArt')
                                        ->orWhere('ImageBoxArt', '/Images/000002.png');
                                });
                            case 'badge':
                                return $query->where(function ($query) {
                                    $query->whereNull('ImageIcon')
                                        ->orWhere('ImageIcon', '/Images/000001.png');
                                });
                            case 'boxart':
                                return $query->where(function ($query) {
                                    $query->whereNull('ImageBoxArt')
                                        ->orWhere('ImageBoxArt', '/Images/000002.png');
                                });
                            case 'title':
                                return $query->where(function ($query) {
                                    $query->whereNull('ImageTitle')
                                        ->orWhere('ImageTitle', '/Images/000002.png');
                                });
                            case 'ingame':
                                return $query->where(function ($query) {
                                    $query->whereNull('ImageIngame')
                                        ->orWhere('ImageIngame', '/Images/000002.png');
                                });
                            default:
                                return $query;
                        }
                    }),

                Tables\Filters\TernaryFilter::make('has_dynamic_rp')
                    ->label('Has dynamic rich presence')
                    ->placeholder('Any')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('RichPresencePatch')
                            ->whereNotIn('ConsoleID', System::getNonGameSystems())
                            ->where(function (Builder $query) {
                                $query->where('RichPresencePatch', 'LIKE', '%@%')
                                    ->orWhere('RichPresencePatch', 'LIKE', '%?%');
                            }),
                        false: fn (Builder $query): Builder => $query
                            ->whereNotIn('ConsoleID', System::getNonGameSystems())
                            ->where(function (Builder $query) {
                                $query->whereNull('RichPresencePatch')
                                    ->orWhere(function (Builder $query) {
                                        $query->where('RichPresencePatch', 'NOT LIKE', '%@%')
                                            ->where('RichPresencePatch', 'NOT LIKE', '%?%');
                                    });
                            }),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                Tables\Filters\TernaryFilter::make('duplicate_achievement_badges')
                    ->label('Has stock/recycled achievement badges')
                    ->placeholder('Any')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('Achievements')
                                ->whereColumn('Achievements.GameID', 'GameData.ID')
                                ->where('Achievements.Flags', AchievementFlag::OfficialCore->value)
                                ->whereNull('Achievements.deleted_at')
                                ->groupBy('Achievements.GameID', 'Achievements.BadgeName')
                                ->havingRaw('COUNT(*) > 1');
                        }),
                        false: fn (Builder $query): Builder => $query->whereNotExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('Achievements')
                                ->whereColumn('Achievements.GameID', 'GameData.ID')
                                ->where('Achievements.Flags', AchievementFlag::OfficialCore->value)
                                ->whereNull('Achievements.deleted_at')
                                ->groupBy('Achievements.GameID', 'Achievements.BadgeName')
                                ->havingRaw('COUNT(*) > 1');
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                    ])->dropdown(false),

                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => GameResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AchievementsRelationManager::class,
            AchievementSetsRelationManager::class,
            ReleasesRelationManager::class,
            LeaderboardsRelationManager::class,
            MemoryNotesRelationManager::class,
            CoreSetAuthorshipCreditsRelationManager::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\Details::class,
            Pages\Hubs::class,
            Pages\SimilarGames::class,
            Pages\Hashes::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'hubs' => Pages\Hubs::route('/{record}/hubs'),
            'similar-games' => Pages\SimilarGames::route('/{record}/similar-games'),
            'hashes' => Pages\Hashes::route('/{record}/hashes'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<Game>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['system', 'forumTopic']);
    }
}
