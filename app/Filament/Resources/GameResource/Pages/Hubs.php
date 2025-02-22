<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\GameSet;
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

class Hubs extends ManageRelatedRecords
{
    protected static string $resource = GameResource::class;

    protected static string $relationship = 'hubs';

    protected static ?string $navigationIcon = 'fas-sitemap';

    public static function canAccess(array $arguments = []): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', GameSet::class);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Livewire::current()->getRecord()->hubs->count();
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('game_sets.id', 'like', "%{$search}");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('game_sets.id', $direction);
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\Action::make('add')
                    ->label('Add hubs')
                    ->form([
                        Forms\Components\Select::make('hub_ids')
                            ->label('Hubs')
                            ->multiple()
                            ->options(function () {
                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereNotIn('id', $this->getOwnerRecord()->hubs->pluck('id'))
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($hub) => [$hub->id => "[{$hub->id}] {$hub->title}"]);
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn ($hub) => [$hub->id => "[{$hub->id}] {$hub->title}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereNotIn('id', $this->getOwnerRecord()->hubs->pluck('id'))
                                    ->where(function ($query) use ($search) {
                                        $query->where('id', 'LIKE', "%{$search}%")
                                            ->orWhere('title', 'LIKE', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($hub) => [$hub->id => "[{$hub->id}] {$hub->title}"]);
                            }),
                    ])
                    ->modalHeading('Add hubs to game')
                    ->modalAutofocus(false)
                    ->action(function (array $data): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        $gameSets = GameSet::whereType(GameSetType::Hub)
                            ->whereIn('id', $data['hub_ids'])
                            ->get();

                        foreach ($gameSets as $gameSet) {
                            (new AttachGamesToGameSetAction())->execute($gameSet, [$game->id]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->tooltip('Remove')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalHeading('Remove hub from game')
                    ->action(function (GameSet $gameSet): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        (new DetachGamesFromGameSetAction())->execute($gameSet, [$game->id]);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed hub from the game.')
                            ->send();
                    }),

                Tables\Actions\Action::make('visit')
                    ->tooltip('View on Site')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconButton()
                    ->url(fn (GameSet $record): string => route('hub.show', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('remove')
                    ->label('Remove selected')
                    ->modalHeading('Remove selected hubs from game')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Collection $gameSets): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        foreach ($gameSets as $gameSet) {
                            (new DetachGamesFromGameSetAction())->execute($gameSet, [$game->id]);
                        }

                        $this->deselectAllTableRecords();

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Removed selected hubs from the game.')
                            ->send();
                    }),
            ]);
    }
}
