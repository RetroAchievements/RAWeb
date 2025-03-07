<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\RelationManagers;

use App\Filament\Actions\ParseIdsFromCsvAction;
use App\Filament\Resources\HubResource;
use App\Models\GameSet;
use App\Platform\Actions\AttachGameLinksToGameSetAction;
use App\Platform\Actions\DetachGameLinksFromGameSetAction;
use App\Platform\Enums\GameSetType;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ParentHubsRelationManager extends RelationManager
{
    protected static string $relationship = 'parents';
    protected static ?string $title = 'Related Hubs';
    protected static ?string $icon = 'fas-sitemap';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->parents->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width'))
                    ->url(function (GameSet $record) {
                        if (request()->user()->can('manage', GameSet::class)) {
                            return HubResource::getUrl('view', ['record' => $record]);
                        }
                    }),

                Tables\Columns\TextColumn::make('parent_game_set_id')
                    ->label('Hub ID')
                    ->sortable()
                    ->searchable()
                    ->url(function (GameSet $record) {
                        if (request()->user()->can('manage', GameSet::class)) {
                            return HubResource::getUrl('view', ['record' => $record]);
                        }
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->sortable()
                    ->searchable()
                    ->url(function (GameSet $record) {
                        if (request()->user()->can('manage', GameSet::class)) {
                            return HubResource::getUrl('view', ['record' => $record]);
                        }
                    }),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\Action::make('add')
                    ->label('Add related hubs')
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
                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereNotIn('id', $this->getOwnerRecord()->parents->pluck('id'))
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($gameSet) => [$gameSet->id => "[{$gameSet->id} {$gameSet->title}]"]);
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return GameSet::whereType(GameSetType::Hub)
                                    ->whereNotIn('id', $this->getOwnerRecord()->parents->pluck('id'))
                                    ->where(function ($query) use ($search) {
                                        $query->where('id', 'LIKE', "%{$search}%")
                                            ->orWhere('title', 'LIKE', "%{$search}%");
                                    })
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($gameSet) => [$gameSet->id => "[{$gameSet->id} {$gameSet->title}]"]);
                            })
                            ->hidden(fn (Forms\Get $get): bool => filled($get('hub_ids_csv')))
                            ->disabled(fn (Forms\Get $get): bool => filled($get('hub_ids_csv')))
                            ->live()
                            ->helperText('... or search and select hubs to add.'),
                    ])
                    ->modalHeading('Add related hub links to hub')
                    ->modalAutofocus(false)
                    ->action(function (array $data): void {
                        /** @var GameSet $gameSet */
                        $gameSet = $this->getOwnerRecord();

                        // Handle select field input.
                        if (!empty($data['hub_ids'])) {
                            (new AttachGameLinksToGameSetAction())->execute($gameSet, $data['hub_ids']);

                            return;
                        }

                        // Handle CSV input.
                        if (!empty($data['hub_ids_csv'])) {
                            $hubIds = (new ParseIdsFromCsvAction())->execute($data['hub_ids_csv']);

                            // Validate that these hubs can be attached.
                            $hubGameSets = GameSet::whereType(GameSetType::Hub)
                                ->whereIn('id', $hubIds)
                                ->whereNotIn('id', $this->getOwnerRecord()->parents->pluck('id'))
                                ->get();

                            foreach ($hubGameSets as $hubGameSet) {
                                (new AttachGameLinksToGameSetAction())->execute($gameSet, [$hubGameSet->id]);
                            }
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
                    ->modalHeading('Remove related hub link from hub')
                    ->action(function (GameSet $gameSetToDetach): void {
                        /** @var GameSet $rootGameSet */
                        $rootGameSet = $this->getOwnerRecord();

                        (new DetachGameLinksFromGameSetAction())->execute($rootGameSet, [$gameSetToDetach->id]);
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
                    ->modalHeading('Remove selected related hub links from hub')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Collection $gameLinks): void {
                        /** @var GameSet $gameSet */
                        $gameSet = $this->getOwnerRecord();

                        (new DetachGameLinksFromGameSetAction())->execute($gameSet, $gameLinks->pluck('parent_game_set_id')->toArray());

                        $this->deselectAllTableRecords();
                    }),

            ])
            ->paginated([50, 100, 150]);
    }
}
