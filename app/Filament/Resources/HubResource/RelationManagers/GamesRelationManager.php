<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\RelationManagers;

use App\Filament\Resources\GameResource;
use App\Filament\Resources\SystemResource;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Platform\Actions\AttachGamesToGameSetAction;
use App\Platform\Actions\DetachGamesFromGameSetAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GamesRelationManager extends RelationManager
{
    protected static string $relationship = 'games';
    protected static ?string $title = 'Games';
    protected static ?string $icon = 'fas-gamepad';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->games->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('system'))
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width'))
                    ->url(function (Game $record) {
                        if (request()->user()->can('manage', Game::class)) {
                            return GameResource::getUrl('view', ['record' => $record]);
                        }
                    }),

                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->url(function (Game $record) {
                        if (request()->user()->can('manage', Game::class)) {
                            return GameResource::getUrl('view', ['record' => $record]);
                        }
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->sortable()
                    ->searchable()
                    ->url(function (Game $record) {
                        if (request()->user()->can('manage', Game::class)) {
                            return GameResource::getUrl('view', ['record' => $record]);
                        }
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
                Tables\Filters\SelectFilter::make('system')
                    ->relationship('system', 'Name'),

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
                // TODO after dropping GameAlternatives, try to use native attach
                Tables\Actions\Action::make('add')
                    ->label('Add games')
                    ->form([
                        Forms\Components\TextInput::make('game_ids_csv')
                            ->label('Game IDs (CSV)')
                            ->placeholder('729,2204,3987,53')
                            ->helperText('Paste a comma-separated list of game IDs.'),

                        Forms\Components\Select::make('game_ids')
                            ->label('Games')
                            ->multiple()
                            ->options(function () {
                                return Game::whereNotIn('ID', $this->getOwnerRecord()->games->pluck('ID'))
                                    ->where('ConsoleID', '!=', System::Hubs)
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->ID => "[{$game->ID}] {$game->Title}"]);
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Game::whereNotIn('ID', $this->getOwnerRecord()->games->pluck('ID'))
                                    ->where('ConsoleID', '!=', System::Hubs)
                                    ->where(function ($query) use ($search) {
                                        $query->where('ID', 'LIKE', "%{$search}%")
                                            ->orWhere('Title', 'LIKE', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->ID => "[{$game->ID}] {$game->Title}"]);
                            })
                            ->helperText('... or search and select games to add.'),
                    ])
                    ->modalHeading('Add games to hub')
                    ->action(function (array $data): void {
                        /** @var GameSet $gameSet */
                        $gameSet = $this->getOwnerRecord();

                        $gameIds = collect();

                        // Add IDs from the select field.
                        if (!empty($data['game_ids'])) {
                            $gameIds = $gameIds->merge($data['game_ids']);
                        }

                        // Add IDs from the CSV field.
                        if (!empty($data['game_ids_csv'])) {
                            $csvIds = collect(explode(',', $data['game_ids_csv']))
                                ->map(fn ($id) => trim($id))
                                ->filter()
                                ->values();

                            // Validate that these games can be attached.
                            $validCsvIds = Game::whereIn('ID', $csvIds)
                                ->where('ConsoleID', '!=', System::Hubs)
                                ->whereNotIn('ID', $this->getOwnerRecord()->games->pluck('ID'))
                                ->pluck('ID');

                            $gameIds = $gameIds->merge($validCsvIds);
                        }

                        if ($gameIds->isEmpty()) {
                            return;
                        }

                        (new AttachGamesToGameSetAction())->execute($gameSet, $gameIds->unique()->toArray());
                    }),
            ])
            ->actions([
                // TODO after dropping GameAlternatives, use native detach
                Tables\Actions\Action::make('remove')
                    ->tooltip('Remove')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalHeading('Remove game from hub')
                    ->action(function (Game $game): void {
                        /** @var GameSet $gameSet */
                        $gameSet = $this->getOwnerRecord();

                        (new DetachGamesFromGameSetAction())->execute($gameSet, [$game->id]);
                    }),

                Tables\Actions\Action::make('visit')
                    ->tooltip('View on Site')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconButton()
                    ->url(fn (Game $record): string => route('game.show', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // TODO after dropping GameAlternatives, use native detach
                Tables\Actions\BulkAction::make('remove')
                    ->label('Remove selected')
                    ->modalHeading('Remove selected games from hub')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Collection $games): void {
                        /** @var GameSet $gameSet */
                        $gameSet = $this->getOwnerRecord();

                        (new DetachGamesFromGameSetAction())->execute($gameSet, $games->pluck('id')->toArray());

                        $this->deselectAllTableRecords();
                    }),
            ])
            ->paginated([50, 100, 150]);
    }
}
