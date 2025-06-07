<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\RelationManagers;

use App\Filament\Actions\ParseIdsFromCsvAction;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\SystemResource;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        /** @var User $user */
        $user = Auth::user();

        /** @var GameSet $gameSet */
        $gameSet = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('system'))
            ->defaultSort('sort_title')
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
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('GameData.ID', $direction);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('GameData.ID', 'like', "%{$search}%");
                    })
                    ->url(function (Game $record) {
                        if (request()->user()->can('manage', Game::class)) {
                            return GameResource::getUrl('view', ['record' => $record]);
                        }
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('sort_title', $direction);
                    })
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
                Tables\Actions\Action::make('add')
                    ->label('Add games')
                    ->visible(fn (): bool => $user->can('update', $gameSet))
                    ->form([
                        Forms\Components\TextInput::make('game_ids_csv')
                            ->label('Game IDs (CSV)')
                            ->placeholder('729,2204,3987,53')
                            ->helperText('Enter game IDs separated by commas or spaces. URLs are also supported.')
                            ->hidden(fn (Forms\Get $get): bool => filled($get('game_ids')))
                            ->disabled(fn (Forms\Get $get): bool => filled($get('game_ids')))
                            ->live(debounce: 200),

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
                            ->hidden(fn (Forms\Get $get): bool => filled($get('game_ids_csv')))
                            ->disabled(fn (Forms\Get $get): bool => filled($get('game_ids_csv')))
                            ->live()
                            ->helperText('... or search and select games to add.'),
                    ])
                    ->modalHeading('Add games to hub')
                    ->action(function (array $data) use ($gameSet, $user): void {
                        if (!$user->can('update', $gameSet)) {
                            return;
                        }

                        // Handle select field input.
                        if (!empty($data['game_ids'])) {
                            $gameSet->games()->attach($data['game_ids']);

                            return;
                        }

                        // Handle CSV input.
                        if (!empty($data['game_ids_csv'])) {
                            $gameIds = (new ParseIdsFromCsvAction())->execute($data['game_ids_csv']);

                            // Validate that these games can be attached.
                            $validGameIds = Game::whereIn('ID', $gameIds)
                                ->where('ConsoleID', '!=', System::Hubs)
                                ->whereNotIn('ID', $this->getOwnerRecord()->games->pluck('ID'))
                                ->pluck('ID')
                                ->toArray();

                            if (!empty($validGameIds)) {
                                $gameSet->games()->attach($validGameIds);
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->tooltip('Remove')
                    ->visible(fn (): bool => $user->can('update', $gameSet))
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalHeading('Remove game from hub')
                    ->action(function (Game $game) use ($gameSet, $user): void {
                        if (!$user->can('update', $gameSet)) {
                            return;
                        }

                        $gameSet->games()->detach([$game->id]);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed game from the hub.')
                            ->send();
                    }),

                Tables\Actions\Action::make('visit')
                    ->tooltip('View on Site')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconButton()
                    ->url(fn (Game $record): string => route('game.show', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('remove')
                    ->label('Remove selected')
                    ->visible(fn (): bool => $user->can('update', $gameSet))
                    ->modalHeading('Remove selected games from hub')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Collection $games): void {
                        /** @var GameSet $gameSet */
                        $gameSet = $this->getOwnerRecord();

                        $gameSet->games()->detach($games->pluck('id')->toArray());

                        $this->deselectAllTableRecords();

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed selected games from the hub.')
                            ->send();
                    }),
            ])
            ->paginated([50, 100, 150]);
    }
}
