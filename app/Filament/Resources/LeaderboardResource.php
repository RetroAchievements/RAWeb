<?php

declare(strict_types=1);

namespace App\Filament\Resources;

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
                Infolists\Components\Section::make('Metadata')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('Title'),

                        Infolists\Components\TextEntry::make('game.title'),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
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
}
