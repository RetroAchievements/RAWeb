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
use App\Rules\UploadedImageAspectRatioRule;
use BackedEnum;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists;
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

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-gamepad';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

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
            'ID' => (string) $record->id,
            'System' => $record->system->name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'title'];
    }

    public static function infolist(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->components([
                Infolists\Components\ImageEntry::make('badge_url')
                    ->label('Badge')
                    ->size(config('media.icon.lg.width')),

                Infolists\Components\SpatieMediaLibraryImageEntry::make('banner')
                    ->label('Banner Image')
                    ->collection('banner')
                    ->conversion('lg-webp'),

                Schemas\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('title'),

                        Infolists\Components\TextEntry::make('sort_title')
                            ->label('Sort Title')
                            ->visible(fn (Game $record): bool => $user->can('updateField', [$record, 'sort_title']) ?? false),

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

                Schemas\Components\Section::make('Metadata')
                    ->icon('heroicon-c-information-circle')
                    ->description('While optional, this metadata can help more players find the game. It also gets fed to various apps plugged in to the RetroAchievements API.')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('developer'),

                        Infolists\Components\TextEntry::make('publisher'),

                        Infolists\Components\TextEntry::make('genre'),

                        Infolists\Components\TextEntry::make('legacy_guide_url')
                            ->label('RAGuide URL')
                            ->placeholder('none')
                            ->url(fn (Game $record): ?string => $record->legacy_guide_url)
                            ->extraAttributes(function (Game $game): array {
                                if ($game->legacy_guide_url) {
                                    return ['class' => 'underline'];
                                }

                                return [];
                            })
                            ->limit(30),
                    ]),

                Schemas\Components\Section::make('Metrics')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->description("
                        Statistics regarding the game's players and achievements can be found here.
                    ")
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Schemas\Components\Fieldset::make('Players')
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

                        Schemas\Components\Fieldset::make('Achievements')
                            ->schema([
                                Infolists\Components\TextEntry::make('achievements_published')
                                    ->label('Promoted')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('achievements_unpublished')
                                    ->label('Unpromoted')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Schemas\Components\Fieldset::make('Score')
                            ->schema([
                                Infolists\Components\TextEntry::make('points_total')
                                    ->label('Points')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('points_weighted')
                                    ->label('RetroPoints')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),
                    ]),

                Schemas\Components\Section::make('Rich Presence')
                    ->icon('heroicon-s-chat-bubble-left-right')
                    ->description('Rich Presence scripts display dynamic game information to players.')
                    ->schema([
                        Infolists\Components\ViewEntry::make('trigger_definition')
                            ->label('Rich Presence Script')
                            ->view('filament.components.rich-presence-script'),
                    ]),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->components([
                Schemas\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Canonical Title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(80)
                            ->disabled(!$user->can('updateField', [$schema->model, 'title'])),

                        Forms\Components\TextInput::make('sort_title')
                            ->label('Sort Title')
                            ->required()
                            ->minLength(2)
                            ->visible(fn () => $user->can('updateField', [$schema->model, 'sort_title']))
                            ->helperText('Normalized title for sorting. DON\'T CHANGE THIS UNLESS YOU KNOW WHAT YOU\'RE DOING.'),

                        Forms\Components\TextInput::make('forum_topic_id')
                            ->label('Forum Topic ID')
                            ->numeric()
                            ->rules([new ExistsInForumTopics()])
                            ->disabled(!$user->can('updateField', [$schema->model, 'forum_topic_id'])),
                    ]),

                Schemas\Components\Section::make('Metadata')
                    ->icon('heroicon-c-information-circle')
                    ->description('While optional, this metadata can help more players find the game. It also gets fed to various apps plugged in to the RetroAchievements API.')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('developer')
                            ->maxLength(50)
                            ->disabled(!$user->can('updateField', [$schema->model, 'developer'])),

                        Forms\Components\TextInput::make('publisher')
                            ->maxLength(50)
                            ->disabled(!$user->can('updateField', [$schema->model, 'publisher'])),

                        Forms\Components\TextInput::make('genre')
                            ->maxLength(50)
                            ->disabled(!$user->can('updateField', [$schema->model, 'genre'])),

                        Forms\Components\TextInput::make('legacy_guide_url')
                            ->label('RAGuide URL')
                            ->url()
                            ->rules([new IsAllowedGuideUrl()])
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->disabled(!$user->can('updateField', [$schema->model, 'legacy_guide_url'])),
                    ]),

                Schemas\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->hidden(
                        !$user->can('updateField', [$schema->model, 'image_icon_asset_path'])
                        && !$user->can('updateField', [$schema->model, 'image_box_art_asset_path'])
                        && !$user->can('updateField', [$schema->model, 'image_title_asset_path'])
                        && !$user->can('updateField', [$schema->model, 'image_ingame_asset_path'])
                    )
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('image_icon_asset_path')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->rules([
                                'dimensions:width=96,height=96',
                            ])
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true)
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_icon_asset_path'])),

                        Forms\Components\FileUpload::make('image_box_art_asset_path')
                            ->label('Box Art')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true)
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_box_art_asset_path'])),

                        Forms\Components\FileUpload::make('image_title_asset_path')
                            ->label('Title')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true)
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_title_asset_path'])),

                        Forms\Components\FileUpload::make('image_ingame_asset_path')
                            ->label('In Game')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true)
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_ingame_asset_path'])),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('banner')
                            ->label('Banner Image')
                            ->collection('banner')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->rules([
                                'dimensions:min_width=1920,min_height=540',
                                new UploadedImageAspectRatioRule(32 / 9, 0.15), // 32:9 aspect ratio with a ±15% tolerance.
                            ])
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(5120)
                            ->maxFiles(1)
                            ->helperText('Upload a high-quality 32:9 ultra-wide banner image (minimum: 1920x540, recommended: 3200x900). The image must be approximately 32:9 aspect ratio (±15% tolerance). The image will be processed to multiple sizes for mobile and desktop.')
                            ->previewable(true)
                            ->downloadable(false)
                            ->hidden(!$user->can('updateField', [$schema->model, 'banner'])),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Rich Presence')
                    ->icon('heroicon-s-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('trigger_definition')
                            ->label('Rich Presence Script')
                            ->maxLength(60000)
                            ->rows(10)
                            ->helperText(new HtmlString('<a href="https://docs.retroachievements.org/developer-docs/rich-presence.html" target="_blank" class="underline">Learn more about Rich Presence</a>'))
                            ->placeholder("Format:Number\nFormatType=VALUE")
                            ->extraInputAttributes(['class' => 'font-mono'])
                            ->disabled(!$user->can('updateField', [$schema->model, 'trigger_definition'])),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('sort_title', $direction)),

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

                Tables\Columns\TextColumn::make('publisher')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('developer')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('genre')
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
                    ->label('Achievements (Promoted)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('achievements_unpublished')
                    ->label('Achievements (Unpromoted)')
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

                Tables\Columns\TextColumn::make('points_weighted')
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
                        $systemOptions = System::orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();

                        return $options + $systemOptions;
                    })
                    ->query(function (Builder $query, $data) {
                        $value = $data['value'] ?? null;

                        if ($value === 'active') {
                            $query->whereIn('system_id', System::active()->pluck('id'));
                        } elseif ($value) {
                            $query->where('system_id', $value);
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

                        $query = $query->whereNotIn('system_id', System::getNonGameSystems());

                        switch ($data['value']) {
                            case 'none':
                                return $query->whereNotNull('image_icon_asset_path')
                                    ->where('image_icon_asset_path', '!=', '/Images/000001.png')
                                    ->whereNotNull('image_title_asset_path')
                                    ->where('image_title_asset_path', '!=', '/Images/000002.png')
                                    ->whereNotNull('image_ingame_asset_path')
                                    ->where('image_ingame_asset_path', '!=', '/Images/000002.png')
                                    ->whereNotNull('image_box_art_asset_path')
                                    ->where('image_box_art_asset_path', '!=', '/Images/000002.png');
                            case 'all':
                                return $query->where(function ($query) {
                                    $query->whereNull('image_icon_asset_path')
                                        ->orWhere('image_icon_asset_path', '/Images/000001.png');
                                })->where(function ($query) {
                                    $query->whereNull('image_title_asset_path')
                                        ->orWhere('image_title_asset_path', '/Images/000002.png');
                                })->where(function ($query) {
                                    $query->whereNull('image_ingame_asset_path')
                                        ->orWhere('image_ingame_asset_path', '/Images/000002.png');
                                })->where(function ($query) {
                                    $query->whereNull('image_box_art_asset_path')
                                        ->orWhere('image_box_art_asset_path', '/Images/000002.png');
                                });
                            case 'any':
                                return $query->where(function ($query) {
                                    $query->whereNull('image_icon_asset_path')
                                        ->orWhere('image_icon_asset_path', '/Images/000001.png')
                                        ->orWhereNull('image_title_asset_path')
                                        ->orWhere('image_title_asset_path', '/Images/000002.png')
                                        ->orWhereNull('image_ingame_asset_path')
                                        ->orWhere('image_ingame_asset_path', '/Images/000002.png')
                                        ->orWhereNull('image_box_art_asset_path')
                                        ->orWhere('image_box_art_asset_path', '/Images/000002.png');
                                });
                            case 'badge':
                                return $query->where(function ($query) {
                                    $query->whereNull('image_icon_asset_path')
                                        ->orWhere('image_icon_asset_path', '/Images/000001.png');
                                });
                            case 'boxart':
                                return $query->where(function ($query) {
                                    $query->whereNull('image_box_art_asset_path')
                                        ->orWhere('image_box_art_asset_path', '/Images/000002.png');
                                });
                            case 'title':
                                return $query->where(function ($query) {
                                    $query->whereNull('image_title_asset_path')
                                        ->orWhere('image_title_asset_path', '/Images/000002.png');
                                });
                            case 'ingame':
                                return $query->where(function ($query) {
                                    $query->whereNull('image_ingame_asset_path')
                                        ->orWhere('image_ingame_asset_path', '/Images/000002.png');
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
                            ->whereNotNull('trigger_definition')
                            ->whereNotIn('system_id', System::getNonGameSystems())
                            ->where(function (Builder $query) {
                                $query->where('trigger_definition', 'LIKE', '%@%')
                                    ->orWhere('trigger_definition', 'LIKE', '%?%');
                            }),
                        false: fn (Builder $query): Builder => $query
                            ->whereNotIn('system_id', System::getNonGameSystems())
                            ->where(function (Builder $query) {
                                $query->whereNull('trigger_definition')
                                    ->orWhere(function (Builder $query) {
                                        $query->where('trigger_definition', 'NOT LIKE', '%@%')
                                            ->where('trigger_definition', 'NOT LIKE', '%?%');
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
                                ->from('achievements')
                                ->whereColumn('achievements.game_id', 'games.id')
                                ->where('achievements.is_promoted', true)
                                ->whereNull('achievements.deleted_at')
                                ->groupBy('achievements.game_id', 'achievements.image_name')
                                ->havingRaw('COUNT(*) > 1');
                        }),
                        false: fn (Builder $query): Builder => $query->whereNotExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('achievements')
                                ->whereColumn('achievements.game_id', 'games.id')
                                ->where('achievements.is_promoted', true)
                                ->whereNull('achievements.deleted_at')
                                ->groupBy('achievements.game_id', 'achievements.image_name')
                                ->havingRaw('COUNT(*) > 1');
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        ViewAction::make(),
                        EditAction::make(),
                    ])->dropdown(false),

                    Actions\Action::make('audit-log')
                        ->url(fn ($record) => GameResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    // DeleteBulkAction::make(),
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
