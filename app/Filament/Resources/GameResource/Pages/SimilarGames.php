<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\ParseIdsFromCsvAction;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\SystemResource;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\LinkSimilarGamesAction;
use App\Platform\Actions\UnlinkSimilarGamesAction;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class SimilarGames extends ManageRelatedRecords
{
    protected static string $resource = GameResource::class;

    protected static string $relationship = 'similarGamesList';

    protected static string|BackedEnum|null $navigationIcon = 'fas-gamepad';

    public function getTitle(): string|Htmlable
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        return "{$game->title} ({$game->system->name_short}) - " . static::getRelationshipTitle();
    }

    public function getBreadcrumb(): string
    {
        return static::getRelationshipTitle();
    }

    public static function canAccess(array $arguments = []): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', GameSet::class);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Livewire::current()->getRecord()->similarGamesList->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->width('60px')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->url(fn (Game $record): string => GameResource::getUrl('view', ['record' => $record]))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(DB::raw('GameData.ID'), $direction);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(DB::raw('GameData.ID'), 'LIKE', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('Title')
                    ->url(fn (Game $record): string => GameResource::getUrl('view', ['record' => $record]))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(DB::raw('GameData.Title'), $direction);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(DB::raw('GameData.Title'), 'LIKE', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('system')
                    ->label('System')
                    ->formatStateUsing(fn (System $state) => "[{$state->id}] {$state->name}")
                    ->url(function (System $state) {
                        if (request()->user()->can('manage', System::class)) {
                            return SystemResource::getUrl('view', ['record' => $state->id]);
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('achievements_published')
                    ->label('Achievements (Published)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
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
            ])
            ->headerActions([
                Actions\Action::make('add')
                    ->label('Add similar games')
                    ->schema([
                        Forms\Components\TextInput::make('game_ids_csv')
                            ->label('Game IDs (CSV)')
                            ->placeholder('729,2204,3987,53')
                            ->helperText('Enter game IDs separated by commas or spaces. URLs are also supported.')
                            ->disabled(fn (Get $get): bool => filled($get('game_ids')))
                            ->live(debounce: 200)
                            ->afterStateUpdated(fn (Set $set) => $set('game_ids', null)),

                        Forms\Components\Select::make('game_ids')
                            ->label('Games')
                            ->multiple()
                            ->options(function () {
                                return Game::whereNot('ID', $this->getOwnerRecord()->id)
                                    ->whereNotIn('ID', $this->getOwnerRecord()->similarGamesList->pluck('ID'))
                                    ->where('ConsoleID', '!=', System::Hubs)
                                    ->limit(50)
                                    ->with('system')
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"]);
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return Game::whereIn('ID', $values)
                                    ->where('ConsoleID', '!=', System::Hubs)
                                    ->with('system')
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Game::whereNot('ID', $this->getOwnerRecord()->id)
                                    ->whereNotIn('ID', $this->getOwnerRecord()->similarGamesList->pluck('ID'))
                                    ->where('ConsoleID', '!=', System::Hubs)
                                    ->where(function ($query) use ($search) {
                                        $query->where('ID', 'LIKE', "%{$search}%")
                                            ->orWhere('Title', 'LIKE', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->with('system')
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"]);
                            })
                            ->disabled(fn (Get $get): bool => filled($get('game_ids_csv')))
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('game_ids_csv', null))
                            ->helperText('... or search and select games to add.'),
                    ])
                    ->modalHeading('Add similar games')
                    ->modalAutofocus(false)
                    ->action(function (array $data): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        $gameIds = [];

                        // Handle select field input.
                        if (!empty($data['game_ids'])) {
                            $gameIds = $data['game_ids'];
                            (new LinkSimilarGamesAction())->execute($game, $gameIds);
                        }

                        // Handle CSV input.
                        if (!empty($data['game_ids_csv'])) {
                            $gameIds = (new ParseIdsFromCsvAction())->execute($data['game_ids_csv']);
                            (new LinkSimilarGamesAction())->execute($game, $gameIds);
                        }

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->send();
                    }),
            ])
            ->recordActions([
                Actions\Action::make('remove')
                    ->tooltip('Remove')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalHeading('Remove similar game')
                    ->action(function (Game $similarGame): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        (new UnlinkSimilarGamesAction())->execute($game, [$similarGame->id]);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed similar game.')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                Actions\BulkAction::make('remove')
                    ->label('Remove selected')
                    ->modalHeading('Remove selected similar games')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Collection $similarGames): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        (new UnlinkSimilarGamesAction())->execute(
                            $game,
                            $similarGames->pluck('id')->toArray()
                        );

                        $this->deselectAllTableRecords();

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed selected similar games.')
                            ->send();
                    }),
            ]);
    }

    /**
     * @param Builder<Game> $query
     * @return Builder<Game>
     */
    protected function modifyQueryWithActiveTab(Builder $query): Builder
    {
        return $query->with(['system']);
    }
}
