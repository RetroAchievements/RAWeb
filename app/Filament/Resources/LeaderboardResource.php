<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\DeleteLeaderboardAction;
use App\Filament\Actions\ResetAllLeaderboardEntriesAction;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\LeaderboardResource\Pages;
use App\Filament\Resources\LeaderboardResource\RelationManagers;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use BackedEnum;
use Filament\Actions;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LeaderboardResource extends Resource
{
    protected static ?string $model = Leaderboard::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-bars-staggered';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Leaderboard $record
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
        return ['id', 'title'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('canonicalUrl')
                            ->label('Permalink')
                            ->formatStateUsing(fn (Leaderboard $record) => url("leaderboardinfo.php?i={$record->id}"))
                            ->url(fn (Leaderboard $record): string => url("leaderboardinfo.php?i={$record->id}"))
                            ->extraAttributes(['class' => 'underline']),

                        Infolists\Components\TextEntry::make('game.title')
                            ->url(function (Leaderboard $record) {
                                if (request()->user()->can('manage', Game::class)) {
                                    return GameResource::getUrl('view', ['record' => $record->game->id]);
                                }

                                return null;
                            })
                            ->extraAttributes(function (): array {
                                if (request()->user()->can('manage', Game::class)) {
                                    return ['class' => 'underline'];
                                }

                                return [];
                            }),

                        Infolists\Components\TextEntry::make('title')
                            ->placeholder('None. Consider setting a title.'),

                        Infolists\Components\TextEntry::make('description'),

                        Infolists\Components\TextEntry::make('order_column')
                            ->label('Display Order'),
                    ]),

                Schemas\Components\Section::make('Rules')
                    ->icon('heroicon-c-wrench-screwdriver')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('format')
                            ->label('Format')
                            ->formatStateUsing(fn (string $state): string => ValueFormat::toString($state)),

                        Infolists\Components\TextEntry::make('rank_asc')
                            ->label('Lower Is Better')
                            ->formatStateUsing(fn (string $state): string => $state === '1' ? 'Yes' : 'No'),
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
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->disabled(!$user->can('updateField', [$schema->model, 'title'])),

                        Forms\Components\TextInput::make('description')
                            ->maxLength(255)
                            ->disabled(!$user->can('updateField', [$schema->model, 'description'])),

                        Forms\Components\TextInput::make('order_column')
                            ->label('Display Order')
                            ->numeric()
                            ->helperText("If set to less than 0, the leaderboard will be invisible to regular players.")
                            ->required()
                            ->disabled(!$user->can('updateField', [$schema->model, 'order_column'])),
                    ]),

                Schemas\Components\Section::make('Rules')
                    ->icon('heroicon-c-wrench-screwdriver')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\Select::make('format')
                            ->options(
                                collect(ValueFormat::cases())
                                    ->mapWithKeys(fn ($format) => [$format => ValueFormat::toString($format)])
                                    ->toArray()
                            )
                            ->required()
                            ->disabled(!$user->can('updateField', [$schema->model, 'format'])),

                        Forms\Components\Toggle::make('rank_asc')
                            ->label('Lower Is Better')
                            ->inline(false)
                            ->helperText('Useful for speedrun leaderboards and similar scenarios.')
                            ->disabled(!$user->can('updateField', [$schema->model, 'rank_asc'])),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Leaderboard')
                    ->description(fn (Leaderboard $record): string => $record->description)
                    ->placeholder(fn (Leaderboard $record): string => $record->description)
                    ->searchable(),

                Tables\Columns\TextColumn::make('game')
                    ->label('Game')
                    ->formatStateUsing(fn (Game $state) => "[{$state->id}] {$state->title}")
                    ->url(fn (Game $state) => GameResource::getUrl('view', ['record' => $state->id]))
                    ->toggleable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('game', function (Builder $subQuery) use ($search) {
                            $subQuery->where('ID', 'like', "%{$search}%")
                                ->orWhere('Title', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('format')
                    ->label('Format')
                    ->formatStateUsing(fn (string $state) => ValueFormat::toString($state))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('entries_count')
                    ->label('Entries')
                    ->counts('entries')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('developer')
                    ->label('Developer')
                    ->formatStateUsing(fn (User $state) => $state?->display_name ?? 'Unknown')
                    ->toggleable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('developer', function (Builder $subQuery) use ($search) {
                            $subQuery->where('User', 'like', "%{$search}%")
                                ->orWhere('display_name', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('order_column')
                    ->label('Display Order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->searchPlaceholder('(ID, Title, Game, Dev)')
            ->filters([
                Tables\Filters\Filter::make('game')
                    ->schema([
                        Forms\Components\Select::make('id')
                            ->label('Game')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return Game::where('Title', 'like', "%{$search}%")
                                    ->orWhere('ID', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($game) {
                                        return [$game->id => "ID: {$game->id} - Title: {$game->title}"];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function (int $value): string {
                                $game = Game::find($value);

                                return "ID: {$game->id} - Title: {$game->title}";
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['id']) {
                            return $query->where('game_id', $data['id']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['id']) {
                            return null;
                        }

                        return "Game {$data['id']}";
                    }),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ActionGroup::make([
                        ResetAllLeaderboardEntriesAction::make('delete_all_entries'),
                        DeleteLeaderboardAction::make('delete_leaderboard'),
                    ])
                        ->dropdown(false),

                    Actions\Action::make('audit-log')
                        ->url(fn ($record) => LeaderboardResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->toolbarActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EntriesRelationManager::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationitems([
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
     * @return Builder<Leaderboard>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['game', 'developer']);
    }

    // Do not allow on-site leaderboard creation.
    public static function canCreate(): bool
    {
        return false;
    }
}
