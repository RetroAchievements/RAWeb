<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\ParseIdsFromCsvAction;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\HubResource;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

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
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->checkIfRecordIsSelectableUsing(fn (GameSet $record): bool => $user->can('update', $record))
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->url(fn (GameSet $record): string => HubResource::getUrl('view', ['record' => $record]))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('game_sets.id', 'like', "%{$search}");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('game_sets.id', $direction);
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->url(fn (GameSet $record): string => HubResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\Action::make('add')
                    ->label('Add hubs')
                    ->form([
                        Forms\Components\TextInput::make('hub_ids_csv')
                            ->label('Hub IDs (CSV)')
                            ->placeholder('729,2204,3987,53')
                            ->helperText('Enter hub IDs separated by commas or spaces. URLs are also supported.')
                            ->hidden(fn (Forms\Get $get): bool => filled($get('hub_ids')))
                            ->disabled(fn (Forms\Get $get): bool => filled($get('hub_ids')))
                            ->live(debounce: 200),

                        Forms\Components\Select::make('hub_ids')
                            ->label('Hubs')
                            ->multiple()
                            ->options(function () {
                                /** @var User $user */
                                $user = Auth::user();

                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereNotIn('id', $this->getOwnerRecord()->hubs->pluck('id'))
                                    ->limit(50)
                                    ->get()
                                    ->filter(fn ($hub) => $user->can('update', $hub))
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
                                /** @var User $user */
                                $user = Auth::user();

                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereNotIn('id', $this->getOwnerRecord()->hubs->pluck('id'))
                                    ->where(function ($query) use ($search) {
                                        $query->where('id', 'LIKE', "%{$search}%")
                                            ->orWhere('title', 'LIKE', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->filter(fn ($hub) => $user->can('update', $hub))
                                    ->mapWithKeys(fn ($hub) => [$hub->id => "[{$hub->id}] {$hub->title}"]);
                            })
                            ->hidden(fn (Forms\Get $get): bool => filled($get('hub_ids_csv')))
                            ->disabled(fn (Forms\Get $get): bool => filled($get('hub_ids_csv')))
                            ->live()
                            ->helperText('... or search and select hubs to add.'),
                    ])
                    ->modalHeading('Add hubs to game')
                    ->modalAutofocus(false)
                    ->action(function (array $data): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        /** @var User $user */
                        $user = Auth::user();

                        // Handle select field input.
                        if (!empty($data['hub_ids'])) {
                            $gameSets = GameSet::whereType(GameSetType::Hub)
                                ->whereIn('id', $data['hub_ids'])
                                ->get();

                            $unauthorizedHubs = [];
                            foreach ($gameSets as $gameSet) {
                                if ($user->can('update', $gameSet)) {
                                    $gameSet->games()->attach([$game->id]);
                                } else {
                                    $unauthorizedHubs[] = $gameSet->title;
                                }
                            }

                            if (!empty($unauthorizedHubs)) {
                                Notification::make()
                                    ->warning()
                                    ->title('Some hubs were not added')
                                    ->body('You do not have permission to update: ' . implode(', ', $unauthorizedHubs))
                                    ->send();
                            }

                            return;
                        }

                        // Handle CSV input.
                        if (!empty($data['hub_ids_csv'])) {
                            $hubIds = (new ParseIdsFromCsvAction())->execute($data['hub_ids_csv']);

                            // Validate that these hubs can be attached.
                            $gameSets = GameSet::whereType(GameSetType::Hub)
                                ->whereIn('id', $hubIds)
                                ->whereNotIn('id', $this->getOwnerRecord()->hubs->pluck('id'))
                                ->get();

                            $unauthorizedHubs = [];
                            foreach ($gameSets as $gameSet) {
                                if ($user->can('update', $gameSet)) {
                                    $gameSet->games()->attach([$game->id]);
                                } else {
                                    $unauthorizedHubs[] = $gameSet->title;
                                }
                            }

                            if (!empty($unauthorizedHubs)) {
                                Notification::make()
                                    ->warning()
                                    ->title('Some hubs were not added')
                                    ->body('You do not have permission to update: ' . implode(', ', $unauthorizedHubs))
                                    ->send();
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->visible(fn ($record): bool => $user->can('update', $record))
                    ->tooltip('Remove')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalHeading('Remove hub from game')
                    ->action(function (GameSet $gameSet): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        $gameSet->games()->detach([$game->id]);

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
                            $gameSet->games()->detach([$game->id]);
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
