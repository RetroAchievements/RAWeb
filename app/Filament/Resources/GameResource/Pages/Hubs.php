<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\BuildGenreCapWarningAction;
use App\Filament\Actions\ParseIdsFromCsvAction;
use App\Filament\Enums\GenreCapWarningSubject;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\HubResource;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use App\Platform\Actions\AttachGamesToHubsAction;
use App\Platform\Enums\GameSetType;
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

class Hubs extends ManageRelatedRecords
{
    protected static string $resource = GameResource::class;

    protected static string $relationship = 'hubs';

    protected static string|BackedEnum|null $navigationIcon = 'fas-sitemap';

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

    public static function getNavigationItems(array $urlParameters = []): array
    {
        $item = parent::getNavigationItems($urlParameters)[0];
        if (($record = $urlParameters['record'] ?? null) instanceof Game) {
            $item->badge((string) $record->hubs->count());
        }

        return [$item];
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->checkIfRecordIsSelectableUsing(fn (GameSet $record): bool => $user->can('update', $record))
            ->defaultSort('sort_title')
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->width('60px')
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
                Actions\Action::make('add')
                    ->label('Add hubs')
                    ->schema([
                        Forms\Components\TextInput::make('hub_ids_csv')
                            ->label('Hub IDs (CSV)')
                            ->placeholder('729,2204,3987,53')
                            ->helperText('Enter hub IDs separated by commas or spaces. URLs are also supported.')
                            ->disabled(fn (Get $get): bool => filled($get('hub_ids')))
                            ->live(debounce: 200)
                            ->afterStateUpdated(fn (Set $set) => $set('hub_ids', null)),

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
                            })
                            ->disableOptionWhen(function (string $value) use ($user): bool {
                                $hub = GameSet::find($value);

                                return !$user->can('update', $hub);
                            })
                            ->disabled(fn (Get $get): bool => filled($get('hub_ids_csv')))
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('hub_ids_csv', null))
                            ->helperText('... or search and select hubs to add.'),
                    ])
                    ->modalHeading('Add hubs to game')
                    ->modalAutofocus(false)
                    ->action(function (array $data) use ($user): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        $selectIds = !empty($data['hub_ids']) ? array_map('intval', $data['hub_ids']) : [];
                        $csvIds = !empty($data['hub_ids_csv'])
                            ? array_map('intval', (new ParseIdsFromCsvAction())->execute($data['hub_ids_csv']))
                            : [];
                        $hubIds = array_values(array_unique(array_merge($selectIds, $csvIds)));

                        if (empty($hubIds)) {
                            return;
                        }

                        // Already-attached hubs are not filtered here. The attach action owns
                        // that check, and it answers more accurately from inside its transaction.
                        $gameSets = GameSet::whereType(GameSetType::Hub)
                            ->whereIn('id', $hubIds)
                            ->get();

                        // We still need to check these as a security measure
                        // because Livewire doesn't actually stop the user from
                        // directly manipulating the form value via browser devtools.
                        [$authorizedHubs, $unauthorizedHubs] = $gameSets->partition(
                            fn (GameSet $gameSet): bool => $user->can('update', $gameSet)
                        );

                        $result = (new AttachGamesToHubsAction())->execute($authorizedHubs, [$game->id]);

                        if ($unauthorizedHubs->isNotEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Some hubs were not added')
                                ->body('You do not have permission to update: ' . $unauthorizedHubs->pluck('title')->implode(', '))
                                ->send();
                        }

                        $skippedHubIds = array_values(array_unique(
                            array_column($result['skippedForGenreCap'], 'hub_id')
                        ));
                        $genreCapWarning = (new BuildGenreCapWarningAction())
                            ->execute($skippedHubIds, GenreCapWarningSubject::Hubs);
                        if ($genreCapWarning !== null) {
                            Notification::make()
                                ->warning()
                                ->title('Genre hub cap reached')
                                ->body($genreCapWarning)
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Actions\Action::make('remove')
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

                Actions\Action::make('visit')
                    ->tooltip('View on Site')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconButton()
                    ->url(fn (GameSet $record): string => route('hub.show', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                Actions\BulkAction::make('remove')
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
