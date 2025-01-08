<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Filament\Resources\SystemResource;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AttachGamesToGameSetAction;
use App\Platform\Actions\DetachGamesFromGameSetAction;
use App\Platform\Enums\GameSetType;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire;

class SimilarGames extends ManageRelatedRecords
{
    protected static string $resource = GameResource::class;

    protected static string $relationship = 'similarGamesList';

    protected static ?string $navigationIcon = 'fas-gamepad';

    public static function canAccess(array $arguments = []): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', GameSet::class);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Livewire::current()->getRecord()->similarGames->count();
    }

    public function table(Table $table): Table
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

                Tables\Columns\TextColumn::make('Title')
                    ->sortable()
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
                Tables\Actions\Action::make('add')
                    ->label('Add similar games')
                    ->form([
                        Forms\Components\Select::make('game_ids')
                            ->label('Games')
                            ->multiple()
                            ->options(function () {
                                return Game::whereNot('ID', $this->getOwnerRecord()->id)
                                    ->limit(50)
                                    ->with('system')
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"]);
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return Game::whereIn('ID', $values)
                                    ->with('system')
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Game::whereNot('ID', $this->getOwnerRecord()->id)
                                    ->where(function ($query) use ($search) {
                                        $query->where('ID', 'LIKE', "%{$search}%")
                                            ->orWhere('Title', 'LIKE', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->with('system')
                                    ->get()
                                    ->mapWithKeys(fn ($game) => [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"]);
                            }),
                    ])
                    ->modalHeading('Add similar games')
                    ->modalAutofocus(false)
                    ->action(function (array $data): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        // This assumes there will only ever be one similar games set.
                        $similarGamesSet = GameSet::whereType(GameSetType::SimilarGames)
                            ->whereGameId($game->id)
                            ->first();

                        (new AttachGamesToGameSetAction())->execute($similarGamesSet, $data['game_ids']);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->tooltip('Remove')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalHeading('Remove similar game')
                    ->action(function (Game $similarGame): void {
                        /** @var Game $rootGame */
                        $rootGame = $this->getOwnerRecord();

                        // This assumes there will only ever be one similar games set.
                        $similarGamesSet = GameSet::whereType(GameSetType::SimilarGames)
                            ->whereGameId($rootGame->id)
                            ->first();

                        (new DetachGamesFromGameSetAction())->execute($similarGamesSet, [$similarGame->id]);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed similar game.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('remove')
                    ->label('Remove selected')
                    ->modalHeading('Remove selected similar games')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Collection $similarGames): void {
                        /** @var Game $rootGame */
                        $rootGame = $this->getOwnerRecord();

                        // This assumes there will only ever be one similar games set.
                        $similarGamesSet = GameSet::whereType(GameSetType::SimilarGames)
                            ->whereGameId($rootGame->id)
                            ->first();

                        foreach ($similarGames as $similarGame) {
                            (new DetachGamesFromGameSetAction())->execute($similarGamesSet, [$similarGame->id]);
                        }

                        $this->deselectAllTableRecords();

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed selected similar games.')
                            ->send();
                    }),
            ]);
    }

    protected function modifyQueryWithActiveTab(Builder $query): Builder
    {
        return $query->with(['system']);
    }
}
