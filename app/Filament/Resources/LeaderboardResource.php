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

class LeaderboardResource extends Resource
{
    protected static ?string $model = Leaderboard::class;

    protected static ?string $navigationIcon = 'fas-bars-staggered';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 5;

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
        return ['ID', 'Title'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Primary Details')
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

                        Infolists\Components\TextEntry::make('Title'),

                        Infolists\Components\TextEntry::make('Description'),

                        Infolists\Components\TextEntry::make('DisplayOrder'),
                    ]),

                Infolists\Components\Section::make('Rules')
                    ->icon('heroicon-c-wrench-screwdriver')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('Format')
                            ->label('Format')
                            ->formatStateUsing(fn (string $state): string => ValueFormat::toString($state)),

                        Infolists\Components\TextEntry::make('LowerIsBetter')
                            ->label('Lower Is Better')
                            ->formatStateUsing(fn (string $state): string => $state === '1' ? 'Yes' : 'No'),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('Description')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('DisplayOrder')
                            ->numeric()
                            ->helperText("If set to less than 0, the leaderboard will be invisible to regular players.")
                            ->required(),
                    ]),

                Forms\Components\Section::make('Rules')
                    ->icon('heroicon-c-wrench-screwdriver')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\Select::make('Format')
                            ->options(
                                collect(ValueFormat::cases())
                                    ->mapWithKeys(fn ($format) => [$format => ValueFormat::toString($format)])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\Toggle::make('LowerIsBetter')
                            ->label('Lower Is Better')
                            ->inline(false)
                            ->helperText('Useful for speedrun leaderboards and similar scenarios.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('Title')
                    ->label('Leaderboard')
                    ->description(fn (Leaderboard $record): string => $record->description)
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

                Tables\Columns\TextColumn::make('Format')
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

                Tables\Columns\TextColumn::make('DisplayOrder')
                    ->label('Display Order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->searchPlaceholder('(ID, Title, Game, Dev)')
            ->filters([
                Tables\Filters\Filter::make('game')
                    ->form([
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
                            return $query->where('GameID', $data['id']);
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        ResetAllLeaderboardEntriesAction::make('delete_all_entries'),
                        DeleteLeaderboardAction::make('delete_leaderboard'),
                    ])
                        ->dropdown(false),

                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => LeaderboardResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->bulkActions([

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
